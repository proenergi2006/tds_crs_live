<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReview;
use App\Models\CustomerReviewAttachment;
use App\Models\CustomerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerVerificationController extends Controller
{
    // GET /api/customer-verifications
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $search  = trim((string) $request->query('search', ''));

        $q = CustomerVerification::query()
            ->with([
                'customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,email,telepon,fax'
            ]);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('token_verification', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('nama_perusahaan', 'like', "%{$search}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $rows = $q->orderByDesc('id_verification')->paginate($perPage);

        // badge "List Link" = customer butuh update & belum dibuatkan link
        $needUpdateCount = Customer::query()
            ->where('need_update', 1)
            ->where('is_generated_link', 0)
            ->count();

        return response()->json([
            'data'               => $rows->items(),
            'current_page'       => $rows->currentPage(),
            'last_page'          => $rows->lastPage(),
            'total'              => $rows->total(),
            'need_update_count'  => $needUpdateCount,
            'total_verification' => (int) $rows->total(),
        ]);
    }

    // GET /api/customer-verifications/{customerVerification}
    public function show(CustomerVerification $customerVerification)
    {
        return $customerVerification->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function getAdminEvaluation($id)
{
    $cv = CustomerVerification::with('customer')->findOrFail($id);

    $customer = $cv->customer;

    return response()->json([
        'top_text'             => $customer->top_payment ? $customer->top_payment . ' Hari' : '-',
        'credit_limit_request' => $customer->credit_limit_diajukan ?? '-',
        'financial_review'     => trim(($customer->jenis_payment ?? '-') . ' — ' . ($customer->jenis_net ?? '-')),
        'potential_volume'     => '-', // Jika belum ada field, isi dummy atau tambahkan nanti
    ]);
}


    // GET /api/verify/{token}  (PUBLIC – dipakai form)
    public function showByToken(string $token)
{
    $row = CustomerVerification::with([
        'customer:id_customer,nama_perusahaan,id_provinsi,id_kabupaten,postal_code,telepon,fax,email,alamat_perusahaan'
    ])->where('token_verification', $token)->firstOrFail();

    return response()->json($row);
}

    // POST /api/customer-verifications
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'token_verification' => ['nullable', 'string', 'size:17', 'unique:customer_verifications,token_verification'],

            'is_evaluated' => ['nullable','integer','in:0,1'],
            'is_reviewed'  => ['nullable','integer','in:0,1'],
            'is_active'    => ['nullable','integer','in:0,1'],

            'legal_data'     => ['nullable','string'],
            'legal_summary'  => ['nullable','string'],
            'legal_result'   => ['nullable','integer'],
            'legal_tgl_proses' => ['nullable','date'],
            'legal_pic'      => ['nullable','string','max:50'],

            'finance_data'    => ['nullable','string'],
            'finance_summary' => ['nullable','string'],
            'finance_result'  => ['nullable','integer'],
            'finance_tgl_proses' => ['nullable','date'],
            'finance_pic'     => ['nullable','string','max:50'],

            'logistik_data'    => ['nullable','string'],
            'logistik_summary' => ['nullable','string'],
            'logistik_result'  => ['nullable','integer'],
            'logistik_tgl_proses' => ['nullable','date'],
            'logistik_pic'     => ['nullable','string','max:50'],

            'sm_summary'  => ['nullable','string'],
            'sm_result'   => ['nullable','integer'],
            'sm_tgl_proses' => ['nullable','date'],
            'sm_pic'      => ['nullable','string','max:50'],

            'om_summary'  => ['nullable','string'],
            'om_result'   => ['nullable','integer'],
            'om_tgl_proses' => ['nullable','date'],
            'om_pic'      => ['nullable','string','max:50'],

            'cfo_summary' => ['nullable','string'],
            'cfo_result'  => ['nullable','integer'],
            'cfo_tgl_proses' => ['nullable','date'],
            'cfo_pic'     => ['nullable','string','max:50'],

            'ceo_summary' => ['nullable','string'],
            'ceo_result'  => ['nullable','integer'],
            'ceo_tgl_proses' => ['nullable','date'],
            'ceo_pic'     => ['nullable','string','max:50'],

            'disposisi_result' => ['nullable','integer'],
            'is_approved'      => ['nullable','integer','in:0,1'],
            'role_approve'     => ['nullable','integer'],
            'tanggal_approved' => ['nullable','date'],
            'jenis_datanya'    => ['nullable','integer'],
            'finance_data_kyc' => ['nullable','string'],
        ]);

        if (empty($data['token_verification'])) {
            $data['token_verification'] = strtoupper(Str::random(17));
        }

        $verification = CustomerVerification::create($data);

        // (opsional) tandai customer sudah dibuatkan link
        Customer::where('id_customer', $data['id_customer'])
            ->update(['is_generated_link' => 1]);

        return response()->json(
            $verification->loadMissing('customer:id_customer,nama_perusahaan'),
            201
        );
    }

    // PUT/PATCH /api/customer-verifications/{customerVerification}  (INTERNAL, tanpa captcha)
    public function update(Request $request, CustomerVerification $customerVerification)
    {
        $data = $request->validate([
            'id_customer'        => ['sometimes','exists:customers,id_customer'],
            'token_verification' => [
                'sometimes','string','size:17',
                Rule::unique('customer_verifications','token_verification')
                    ->ignore($customerVerification->id_verification, 'id_verification'),
            ],

            'is_evaluated' => ['sometimes','integer','in:0,1'],
            'is_reviewed'  => ['sometimes','integer','in:0,1'],
            'is_active'    => ['sometimes','integer','in:0,1'],

            'legal_data'     => ['sometimes','nullable','string'],
            'legal_summary'  => ['sometimes','nullable','string'],
            'legal_result'   => ['sometimes','nullable','integer'],
            'legal_tgl_proses' => ['sometimes','nullable','date'],
            'legal_pic'      => ['sometimes','nullable','string','max:50'],

            'finance_data'    => ['sometimes','nullable','string'],
            'finance_summary' => ['sometimes','nullable','string'],
            'finance_result'  => ['sometimes','nullable','integer'],
            'finance_tgl_proses' => ['sometimes','nullable','date'],
            'finance_pic'     => ['sometimes','nullable','string','max:50'],

            'logistik_data'    => ['sometimes','nullable','string'],
            'logistik_summary' => ['sometimes','nullable','string'],
            'logistik_result'  => ['sometimes','nullable','integer'],
            'logistik_tgl_proses' => ['sometimes','nullable','date'],
            'logistik_pic'     => ['sometimes','nullable','string','max:50'],

            'sm_summary'  => ['sometimes','nullable','string'],
            'sm_result'   => ['sometimes','nullable','integer'],
            'sm_tgl_proses' => ['sometimes','nullable','date'],
            'sm_pic'      => ['sometimes','nullable','string','max:50'],

            'om_summary'  => ['sometimes','nullable','string'],
            'om_result'   => ['sometimes','nullable','integer'],
            'om_tgl_proses' => ['sometimes','nullable','date'],
            'om_pic'      => ['sometimes','nullable','string','max:50'],

            'cfo_summary' => ['sometimes','nullable','string'],
            'cfo_result'  => ['sometimes','nullable','integer'],
            'cfo_tgl_proses' => ['sometimes','nullable','date'],
            'cfo_pic'     => ['sometimes','nullable','string','max:50'],

            'ceo_summary' => ['sometimes','nullable','string'],
            'ceo_result'  => ['sometimes','nullable','integer'],
            'ceo_tgl_proses' => ['sometimes','nullable','date'],
            'ceo_pic'     => ['sometimes','nullable','string','max:50'],

            'disposisi_result' => ['sometimes','nullable','integer'],
            'is_approved'      => ['sometimes','integer','in:0,1'],
            'role_approve'     => ['sometimes','nullable','integer'],
            'tanggal_approved' => ['sometimes','nullable','date'],
            'jenis_datanya'    => ['sometimes','nullable','integer'],
            'finance_data_kyc' => ['sometimes','nullable','string'],
        ]);

        $customerVerification->update($data);

        return $customerVerification->fresh()->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function upload(Request $request, CustomerVerification $customerVerification)
    {
        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,siup_file,tdp_file,other_file',
        ]);

        $path = $request->file('file')->store(
            "customer_verifications/{$customerVerification->id_verification}/{$request->field}",
            'public'
        );

        // simpan path ke legal_data.files[field][]
        $legal = json_decode($customerVerification->legal_data ?? '{}', true) ?: [];
        $legal['files'][$request->field][] = $path;
        $customerVerification->update(['legal_data' => json_encode($legal)]);

        return response()->json(['path' => Storage::url($path)]);
    }

    // ====== PUBLIC UPLOAD (no auth, by token) ======
    public function uploadByToken(Request $request, string $token)
    {
        $cv = CustomerVerification::where('token_verification', $token)
            ->where('is_active', 1)->firstOrFail();

        // (opsional) kalau mau pakai captcha juga di upload, tambahkan validasi di sini

        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,siup_file,tdp_file,other_file',
        ]);

        $path = $request->file('file')->store(
            "customer_verifications/{$cv->id_verification}/{$request->field}",
            'public'
        );

        $legal = json_decode($cv->legal_data ?? '{}', true) ?: [];
        $legal['files'][$request->field][] = $path;
        $cv->update(['legal_data' => json_encode($legal)]);

        return response()->json(['path' => Storage::url($path)]);
    }

    public function updateByToken(Request $request, string $token)
    {
        // 1) CAPTCHA (wajib)
        $request->validate([
            'captcha_key'   => 'required|string',
            'captcha_text'  => 'required|string',
            'legal_data'    => 'required|string',
            'finance_data'  => 'required|string',
            'logistik_data' => 'required|string',
        ]);
        $expected = Cache::get('captcha:'.$request->captcha_key);
        if (!$expected || strtoupper(trim($request->captcha_text)) !== $expected) {
            return response()->json(['message' => 'Captcha tidak valid'], 422);
        }
        Cache::forget('captcha:'.$request->captcha_key);

        // 2) Ambil verification + customer
        $cv = CustomerVerification::where('token_verification', $token)
            ->where('is_active', 1)
            ->firstOrFail();

        $legal    = json_decode($request->legal_data, true)    ?: [];
        $finance  = json_decode($request->finance_data, true)  ?: [];
        $logistik = json_decode($request->logistik_data, true) ?: [];

        // 2a) Validasi agreement
        $agreement = Arr::get($legal, 'agreement');
        if (empty(Arr::get($agreement, 'updated_by')) || empty(Arr::get($agreement, 'agree'))) {
            return response()->json(['message' => 'Agreement wajib diisi.'], 422);
        }

        // 2b) Potong data dari struktur form
        $corp      = Arr::get($legal,   'corporate',  []);
        $reg       = Arr::get($legal,   'registered', []);
        $delivery  = Arr::get($legal,   'delivery',   []);
        $inv       = Arr::get($finance, 'invoice',    []);
        $pay       = Arr::get($finance, 'payment',    []);
        $supply    = Arr::get($logistik,'supply',     []);
        $lg        = Arr::get($logistik,'logistic',   []);

        // 2c) Helper konversi
        $intOrNull = function ($v) {
            if ($v === '' || $v === null) return null;
            if (is_string($v)) {
                // ambil angka depan, buang huruf (contoh "8 KL" -> 8)
                if (preg_match('/^\d+/', $v, $m)) return (int) $m[0];
                return is_numeric($v) ? (int)$v : null;
            }
            return is_numeric($v) ? (int)$v : null;
        };
        $boolToInt = fn($v) => $v ? 1 : 0;

        // mapping kode yg kolomnya bertipe integer
        $mapTipeBisnis = [
            'Agriculture & Forestry / Horticulture' => 1,
            'Business & Information'                => 2,
            'Construction / Utilities / Contracting'=> 3,
            'Education'                              => 4,
            'Finance & Insurance'                    => 5,
            'Food & hospitality'                     => 6,
            'Gaming'                                  => 7,
            'Health Services'                         => 8,
            'Motor Vehicle'                           => 9,
            'Other'                                   => 99,
        ];
        $mapOwnership = [
            'Affiliation'     => 1,
            'National Private' => 2,
            'Foreign Private'  => 3,
            'Joint Venture'    => 4,
            'BUMN / BUMD'      => 5,
            'Foundation'       => 6,
            'Personal'         => 7,
            'Other'            => 99,
        ];
        $mapEnv = ['Industri'=>1,'Pemukiman'=>2,'Other'=>9];
        $mapStorage = ['Indoor'=>1,'Outdoor'=>2,'Other'=>9];
        $mapHours = ['08.00 - 17.00'=>1,'24 Hours'=>2,'Other'=>9];
        $mapVolume = ["PRO ENERGY'S TANK LORRY"=>1,'Flowmeter'=>2,'Other'=>9];
        $mapQuality = ['DENSITY'=>1,'OTHER'=>2]; // else null
        $mapSchedule = ['Every Day'=>1,'Other'=>9];
        $mapPayMethod = ['Cash'=>1,'Transfer'=>2,'Cheque / Giro'=>3,'Bank Guarantee'=>4,'Other'=>9];

        $tipeBisnisCode = $mapTipeBisnis[Arr::get($corp, 'tipe_bisnis', '')] ?? null;
        $ownershipCode  = $mapOwnership[Arr::get($corp, 'ownership', '')] ?? null;

        // 3) Transaksi write multi tabel
        DB::transaction(function () use (
            $cv,$corp,$reg,$delivery,$inv,$pay,$supply,$lg,$agreement,
            $intOrNull,$boolToInt,$tipeBisnisCode,$ownershipCode,
            $mapEnv,$mapStorage,$mapHours,$mapVolume,$mapQuality,$mapSchedule,$mapPayMethod,
            $legal,$finance,$logistik
        ) {
            // ---------- customers ----------
            DB::table('customers')
              ->where('id_customer', $cv->id_customer)
              ->update([
                  'nama_perusahaan'       => Arr::get($corp, 'nama'),
                  'alamat_perusahaan'     => Arr::get($corp, 'alamat'),

                  'id_provinsi'           => $intOrNull(Arr::get($corp, 'id_provinsi')),
                  'id_kabupaten'          => $intOrNull(Arr::get($corp, 'id_kabupaten')),
                  'postal_code'           => Arr::get($corp, 'postal_code'),

                  'telepon'               => Arr::get($corp, 'telepon'),
                  'fax'                   => Arr::get($corp, 'fax'),
                  'email'                 => Arr::get($corp, 'email'),
                  'website_customer'      => Arr::get($corp, 'website'),

                  'tipe_bisnis'           => $tipeBisnisCode, // smallint
                  'tipe_bisnis_lain'      => Arr::get($corp, 'tipe_bisnis_lain'),
                  'ownership'             => $ownershipCode,  // smallint
                  'ownership_lain'        => Arr::get($corp, 'ownership_lain'),
                  'induk_perusahaan'      => Arr::get($corp, 'holding'),

                  'kecamatan_customer'    => Arr::get($corp, 'kecamatan'),
                  'kelurahan_customer'    => Arr::get($corp, 'kelurahan'),

                  'lastupdate_time'       => now(),
                  'lastupdate_by'         => Arr::get($agreement ?? [], 'updated_by'),
                  'count_update'          => DB::raw('COALESCE(count_update,0)+1'),
              ]);

            // ---------- customer_contacts ----------
            DB::table('customer_contacts')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                [
                    'pic_invoice_name'     => Arr::get($inv, 'pic.name'),
                    'pic_invoice_position' => Arr::get($inv, 'pic.position'),
                    'pic_invoice_telp'     => Arr::get($inv, 'pic.telephone'),
                    'pic_invoice_mobile'   => Arr::get($inv, 'pic.mobile'),
                    'pic_invoice_email'    => Arr::get($inv, 'pic.email'),
                    'invoice_delivery_addr_primary'   => Arr::get($inv, 'delivery_address'),
                    'invoice_delivery_addr_secondary' => null,
                    'product_delivery_address'        => json_encode([
                        'alamat1' => Arr::get($delivery, 'alamat1'),
                        'alamat2' => Arr::get($delivery, 'alamat2'),
                        'alamat3' => Arr::get($delivery, 'alamat3'),
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );

            // ---------- customer_payment ----------
            $payUpdate = [
                'email_billing'          => Arr::get($reg, 'email'),
                'alamat_billing'         => Arr::get($reg, 'alamat'),
                'prov_billing'           => $intOrNull(Arr::get($reg, 'id_provinsi')),
                'kab_billing'            => $intOrNull(Arr::get($reg, 'id_kabupaten')),
                'postalcode_billing'     => Arr::get($corp, 'postal_code'),
                'telp_billing'           => Arr::get($corp, 'telepon'),
                'fax_billing'            => Arr::get($corp, 'fax'),

                'payment_schedule'       => $mapSchedule[Arr::get($pay, 'schedule', '')] ?? null,
                'payment_schedule_other' => Arr::get($pay, 'schedule_other'),
                'payment_method'         => $mapPayMethod[Arr::get($pay, 'payment_method', '')] ?? null,
                'payment_method_other'   => Arr::get($pay, 'payment_method_other'),
                'invoice'                => $boolToInt(Arr::get($pay, 'invoice_tax')),
                'ket_extra'              => Arr::get($pay, 'note'),
                'kecamatan_billing'      => Arr::get($corp, 'kecamatan'),
                'kelurahan_billing'      => Arr::get($corp, 'kelurahan'),

                'calculate_method'       => Arr::get($pay, 'pricing_method'),
                'bank_name'              => Arr::get($pay, 'bank_name'),
                'curency'                => Arr::get($pay, 'currency'),
                'bank_address'           => Arr::get($pay, 'bank_address'),
                'account_number'         => Arr::get($pay, 'account_number'),
                'credit_facility'        => $boolToInt(Arr::get($pay, 'has_credit')),
                'creditor'               => Arr::get($pay, 'creditor_name'),
            ];
            // FK jangan diisi 0 → jika null, hapus supaya tak menabrak constraint NOT NULL
            if (is_null($payUpdate['prov_billing'])) unset($payUpdate['prov_billing']);
            if (is_null($payUpdate['kab_billing']))  unset($payUpdate['kab_billing']);

            DB::table('customer_payment')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                $payUpdate
            );

            // ---------- customer_logistik ----------
            $logistikUpdate = [
                'logistik_area'          => Arr::get($lg, 'area'),
                'logistik_bisnis'        => Arr::get($lg, 'security_env'),
                'logistik_env'           => $mapEnv[Arr::get($lg, 'env', '')] ?? null,
                'logistik_env_other'     => Arr::get($lg, 'env_other'),
                'logistik_storage'       => $mapStorage[Arr::get($lg, 'storage', '')] ?? null,
                'logistik_storage_other' => Arr::get($lg, 'storage_other'),
                'logistik_hour'          => $mapHours[Arr::get($lg, 'operating_hours', '')] ?? null,
                'logistik_hour_other'    => Arr::get($lg, 'operating_hours_other'),
                'logistik_volume'        => $mapVolume[Arr::get($lg, 'volume_measurement', '')] ?? null,
                'logistik_volume_other'  => Arr::get($lg, 'volume_measurement_other'),
                'logistik_quality'       => $mapQuality[
                                              Arr::get($lg, 'quality_density') ? 'DENSITY'
                                                : (Arr::get($lg, 'quality_other_enabled') ? 'OTHER' : '')
                                            ] ?? null,
                'logistik_quality_other' => Arr::get($lg, 'quality_other'),
                'logistik_truck'         => $intOrNull(Arr::get($lg, 'max_truck_capacity')), // "8 KL" -> 8
                'logistik_truck_other'   => null,
            
                // ⬇️ yang bikin error: pastikan integer/NULL
                'supply_shceme'          => $intOrNull(Arr::get($supply, 'scheme_details')),
                'specify_product'        => $intOrNull(Arr::get($supply, 'specify_product')), // pakai ini jika kolom INT
                'volume_per_month'       => $intOrNull(Arr::get($supply, 'volume_per_month')),
                'nico'                   => $intOrNull(Arr::get($supply, 'inco_terms')),       // kalau kolom INT
            
                // kalau kolom2 di atas ternyata TEXT/VARCHAR, cukup ganti ke Arr::get(..., '')
                // dan hapus $intOrNull
                'operational_hour_from'  => Arr::get($supply, 'operational_from'),
                'operational_hour_to'    => Arr::get($supply, 'operational_to'),
            ];
            DB::table('customer_logistik')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                $logistikUpdate
            );

            // ---------- flag verifikasi ----------
            DB::table('customer_verifications')
                ->where('id_verification', $cv->id_verification)
                ->update(['is_evaluated' => 1]);

            // ---------- simpan snapshot JSON ----------
            $cv->update([
                'legal_data'    => json_encode($legal,    JSON_UNESCAPED_UNICODE),
                'finance_data'  => json_encode($finance,  JSON_UNESCAPED_UNICODE),
                'logistik_data' => json_encode($logistik, JSON_UNESCAPED_UNICODE),
                'is_evaluated'  => 1,
            ]);
        });

        return response()->json(['message' => 'Data berhasil diperbarui.']);
    }
    
        
    // DELETE /api/customer-verifications/{customerVerification}
    public function destroy(CustomerVerification $customerVerification)
    {
        $customerVerification->delete();
        return response()->noContent();
    }


    public function reviewStats()
{
    // jumlah item yang "siap di-review"
    $unreviewed = CustomerVerification::query()
        ->where('is_evaluated', 1)
        ->where('is_reviewed', 0)
        ->where('is_active', 1)
        ->count();

    // optional: jumlah yang sudah di-review
    $reviewed = CustomerVerification::query()
        ->where('is_evaluated', 1)
        ->where('is_reviewed', 1)
        ->where('is_active', 1)
        ->count();

    return response()->json([
        'unreviewed' => $unreviewed,
        'reviewed'   => $reviewed,
    ]);
}

public function reviewIndex(Request $r)
{
    $per    = (int) $r->query('per_page', 25);
    $q      = trim((string) $r->query('q', ''));
    // baca 'tab' (fallback ke 'status' untuk kompatibilitas lama)
    $status = $r->query('tab', $r->query('status', 'unreviewed'));

    $rows = \App\Models\CustomerVerification::query()
        ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
        ->when($status === 'unreviewed', fn($w) =>
            $w->where('is_evaluated', 1)->where('is_reviewed', 0)->where('is_active', 1)
        )
        ->when($status === 'reviewed', fn($w) =>
            $w->where('is_reviewed', 1)->where('is_active', 1) // tambahkan is_active bila perlu
        )
        ->when($q !== '', function ($w) use ($q) {
            $w->whereHas('customer', function($c) use ($q){
                $c->where('nama_perusahaan','like',"%{$q}%")
                  ->orWhere('alamat_perusahaan','like',"%{$q}%")
                  ->orWhere('kode_pelanggan','like',"%{$q}%");
            });
        })
        ->orderByDesc('id_verification')
        ->paginate($per);

    return response()->json([
        'data'         => $rows->items(),
        'current_page' => $rows->currentPage(),
        'last_page'    => $rows->lastPage(),
        'total'        => $rows->total(),
    ]);
}


public function setReviewed(Request $r, int $id)
{
    $row = \App\Models\CustomerVerification::findOrFail($id);
    $row->update(['is_reviewed' => 1]);
    return response()->json(['ok' => true]);
}

// public function reviewShow(int $id)
// {
//     $row = \App\Models\CustomerVerification::with([
//         'customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax,email'
//     ])->findOrFail($id);

//     return response()->json([
//         'id_verification'    => $row->id_verification,
//         'token_verification' => $row->token_verification,
//         'is_evaluated'       => (int) $row->is_evaluated,
//         'is_reviewed'        => (int) $row->is_reviewed,
//         'is_active'          => (int) $row->is_active,

//         'customer'           => $row->customer,                 // ringkasan customer
//         'legal'              => json_decode($row->legal_data    ?? '[]', true) ?: [],
//         'finance'            => json_decode($row->finance_data  ?? '[]', true) ?: [],
//         'logistik'           => json_decode($row->logistik_data ?? '[]', true) ?: [],
//     ]);
// }

public function reviewShow(int $id)
{
    $cv = CustomerVerification::with([
        'customer:id_customer,nama_perusahaan,alamat_perusahaan,telepon,fax,email'
    ])->findOrFail($id);

    return response()->json([
        'customer'     => $cv->customer,
        'disposisi_result'=> (int) $cv->disposisi_result,
        'is_reviewed'     => (int) $cv->is_reviewed,
        'stage_text'      => match((int) $cv->disposisi_result) {
            0 => 'Marketing/Draft', 1 => 'Admin', 2 => 'Logistik', 3 => 'BM', 4 => 'OM', default => '-',
        },
        'legal'        => json_decode($cv->legal_data    ?? '[]', true) ?: [],
        'finance'      => json_decode($cv->finance_data  ?? '[]', true) ?: [],
        'logistik'     => json_decode($cv->logistik_data ?? '[]', true) ?: [],
        'review_form'  => (function () use ($cv) {
            $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
            return $kyc['form'] ?? [];
        })(),
        // 'review_files' => $kyc['files'] ?? [],
    ]);
}

public function evaluationShow(int $id)
{
    $cv  = CustomerVerification::findOrFail($id);
    $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];

    return response()->json([
        'jenis_datanya'     => (int) ($cv->jenis_datanya ?? 0),
        'disposisi_result'  => (int) ($cv->disposisi_result ?? 0),
        'evaluation'        => $kyc['evaluation'] ?? [],
    ]);
}

public function evaluationAdmin(int $id)
{
    $cv = CustomerVerification::with('customer')->findOrFail($id);
    $cust = $cv->customer;

    $form = [
        'top_text'             => $cust->top_payment ? $cust->top_payment . ' Hari' : null,
        'potential_volume'     => $cust->potential_volume ?? null,
        'credit_limit_request' => $cust->credit_limit_diajukan ?? null,
        'financial_review'     => $cust->financial_review ?? null,
    ];

    return response()->json(['form' => $form]);
}



public function evaluationSave(Request $r, int $id)
{
    $cv = CustomerVerification::findOrFail($id);

    $data = $r->validate([
        'jenis_data'         => 'required|in:before,after',
        'financial_review'   => 'nullable|string',
        'logistic_summary'   => 'nullable|string',
        'logistic_result'    => 'nullable|string',
        'assessment_result'  => 'nullable|integer|in:1,2,3',

        'approval'                 => 'nullable|array',
        'approval.cl_approved'     => 'nullable|string',
        'approval.payment_type'    => 'nullable|in:CREDIT,CASH',
        'approval.top_days'        => 'nullable|integer',
        'approval.top_term'        => 'nullable|string|max:50',
        'approval.group_company'   => 'nullable|string|max:200',
        'approval.docs'            => 'nullable|array',
        'approval.jenis_net'       => 'nullable|string|max:100',
        'approval.dokumen_lainnya' => 'nullable|string|max:255',
        'approval.credit_limit'    => 'nullable|numeric',
    ]);

    $jenisInt = $data['jenis_data'] === 'after' ? 2 : 1;

    // Update evaluation data di json
    $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
    $evaluation = $kyc['evaluation'] ?? [];

    $evaluation['jenis_data']        = $data['jenis_data'];
    $evaluation['financial_review']  = $data['financial_review'] ?? null;
    $evaluation['logistic_summary']  = $data['logistic_summary'] ?? null;
    $evaluation['logistic_result']   = $data['logistic_result'] ?? null;
    $evaluation['assessment_result'] = $data['assessment_result'] ?? null;

    if ($data['jenis_data'] === 'after') {
        $approval = $data['approval'] ?? [];
        $evaluation['approval'] = [
            'cl_approved'   => $approval['cl_approved'] ?? null,
            'payment_type'  => $approval['payment_type'] ?? null,
            'top_days'      => $approval['top_days'] ?? null,
            'top_term'      => $approval['top_term'] ?? null,
            'group_company' => $approval['group_company'] ?? null,
            'jenis_net'     => $approval['jenis_net'] ?? null,
            'docs'          => $approval['docs'] ?? [],
        ];

        // ✅ Sementara jenis_net dikomentari karena tabel customers.jenis_net bertipe integer
        DB::table('customers')->where('id_customer', $cv->id_customer)->update([
            'dokumen_lainnya' => $approval['dokumen_lainnya'] ?? null,
            'credit_limit'    => $approval['credit_limit'] ?? null,
            'jenis_payment'   => $approval['payment_type'] ?? null,
            'top_payment'     => $approval['top_days'] ?? null,
            // 'jenis_net'       => $approval['jenis_net'] ?? null, // ❌ Komentar karena string tidak bisa masuk kolom integer
            'lastupdate_time' => now(),
        ]);
    } else {
        unset($evaluation['approval']);
    }

    $kyc['evaluation'] = $evaluation;

    $cv->update([
        'jenis_datanya'     => $jenisInt,
        'finance_data_kyc'  => $kyc,
    ]);

    return response()->json(['ok' => true]);
}



public function evaluationUploadFile(Request $r, int $id)
{
    $cv = CustomerVerification::findOrFail($id);

    $r->validate([
        'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
        'name' => 'required|string|max:200',
        'kind' => 'required|in:other_doc,approval_file',
    ]);

    $file = $r->file('file');
    $path = $file->store("customer_verifications/{$cv->id_verification}/evaluation", 'public');

    $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
    $kyc['evaluation'] = $kyc['evaluation'] ?? [];

    $payload = [
        'name' => $r->input('name'),
        'path' => $path,
        'url'  => \Storage::disk('public')->url($path),
    ];

    if ($r->input('kind') === 'other_doc') {
        $kyc['evaluation']['other_doc'] = $payload;
    } else {
        $list = $kyc['evaluation']['approval_files'] ?? [];
        $list[] = $payload;
        $kyc['evaluation']['approval_files'] = $list;
    }

    $cv->update(['finance_data_kyc' => $kyc]);

    return response()->json($payload);
}




public function saveReviewData(Request $r, int $id)
{
    $cv = CustomerVerification::findOrFail($id);
    $payload = $r->validate([
        'review_form' => 'required|array',
    ]);

    $kyc = $cv->finance_data_kyc ?? [];
    $kyc['form'] = $payload['review_form'];

    $cv->update([
        'finance_data_kyc' => $kyc,
    ]);

    return response()->json(['ok' => true]);
}

public function uploadReviewFile(Request $r, int $id)
{
    $cv = CustomerVerification::findOrFail($id);

    $r->validate([
        'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar'
    ]);

    $path = $r->file('file')->store("customer_verifications/{$cv->id_verification}/review", 'public');

    $kyc = $cv->finance_data_kyc ?? [];
    $kyc['files'] = $kyc['files'] ?? [];
    $kyc['files'][] = [
        'name' => $r->file('file')->getClientOriginalName(),
        'path' => $path,
        'url'  => Storage::disk('public')->url($path),
    ];

    $cv->update(['finance_data_kyc' => $kyc]);

    return response()->json(end($kyc['files']));
}


public function getReview(int $id)
{
    $review = CustomerReview::where('id_verification', $id)->first();

    $attachments = [];
    if ($review) {
        $attachments = DB::table('customer_review_attchment')   // <-- perbaiki nama tabel
            ->where('id_review', $review->id_review)
            ->where('id_verification', $id)
            ->orderBy('no_urut')
            ->get()
            ->map(function ($r) {
                $r->url = Storage::disk('public')->url($r->review_attach);
                return $r;
            });
    }

    return response()->json([
        'review'      => $review,
        'attachments' => $attachments,
    ]);
}

public function saveReview(Request $r, int $id)
{
    // data inti review
    $data = $r->validate([
        'review_result'            => 'nullable|integer',
        'review_pic'               => 'nullable|string|max:50',
        'review_summary'           => 'nullable|string',
        'jenis_asset'              => 'nullable|string|max:500',
        'kelengkapan_dok_tagihan'  => 'nullable|string|max:500',
        'alur_proses_periksaan'    => 'nullable|string|max:500',
        'jadwal_penerimaan'        => 'nullable|string|max:500',
        'background_bisnis'        => 'nullable|string|max:500',
        'lokasi_depo'              => 'nullable|string|max:500',
        'opportunity_bisnis'       => 'nullable|string|max:500',

        'review1'  => 'nullable|string|max:500',
        'review2'  => 'nullable|string|max:500',
        'review3'  => 'nullable|string|max:500',
        'review4'  => 'nullable|string|max:500',
        'review5'  => 'nullable|string|max:500',
        'review6'  => 'nullable|string|max:500',
        'review7'  => 'nullable|string|max:500',
        'review8'  => 'nullable|string|max:500',
        'review9'  => 'nullable|string|max:500',
        'review10' => 'nullable|string|max:500',
        'review11' => 'nullable|string|max:500',
        'review12' => 'nullable|string|max:500',
        'review13' => 'nullable|string|max:500',
        'review14' => 'nullable|string|max:500',
        'review15' => 'nullable|string|max:500',
        'review16' => 'nullable|string|max:500',

        // sumber nilai CL diajukan (boleh kirim salah satu)
        'credit_limit_diajukan'    => 'nullable|string',
        'review_form'              => 'nullable|array', // bila kamu tetap kirim nested form dari FE
        // control hapus attachment lama (mirip delete all sebelum insert)
        'reset_attachments'        => 'sometimes|boolean',
    ]);

    // helper: ambil CL dari payload (prioritas: field langsung, fallback dari review_form.detail.credit_limit_proposed)
    $rawCL = $data['credit_limit_diajukan']
    ?? data_get($data, 'review_form.detail.credit_limit_proposed')
    ?? ($data['review1'] ?? null); 

    // normalisasi ke integer (ambil angka saja, "10.000.000" -> 10000000)
    $creditLimit = null;
if (!is_null($rawCL)) {
    $creditLimit = (int) preg_replace('/\D+/', '', (string) $rawCL);
}
    $cv = CustomerVerification::select('id_verification','id_customer')->findOrFail($id);

    DB::transaction(function () use ($cv, $data, $creditLimit, $r) {

        // 1) upsert review
        $row = CustomerReview::updateOrCreate(
            ['id_verification' => $cv->id_verification],
            array_merge($data, [
                'review_tanggal' => now(),
                'review_pic'     => $data['review_pic'] ?? (auth()->user()->name ?? null),
            ])
        );

        // 2) set reviewed & disposisi_result = 0 (sesuai SQL lama)
        CustomerVerification::where('id_verification', $cv->id_verification)
            ->update([
                'is_reviewed'      => 1,
                'disposisi_result' => 0,
            ]);

            $affected = DB::table('customers')
    ->where('id_customer', $cv->id_customer)
    ->update(['credit_limit_diajukan' => $creditLimit]);

        // 3) update credit_limit_diajukan di customers bila ada nilainya
        if ($affected === 0) {
            \Log::warning('CL not updated', [
                'id_customer' => $cv->id_customer,
                'credit_limit' => $creditLimit
            ]);
        }

        // 4) OPSIONAL: hapus semua attachment lama (meniru "DELETE FROM ... WHERE id_review = ?")
        if ($r->boolean('reset_attachments')) {
            CustomerReviewAttachment::where('id_review', $row->id_review)
                ->where('id_verification', $cv->id_verification)
                ->delete();
        }
    });

    return response()->json([
        'ok' => true,
        'message' => 'Review tersimpan & status diverifikasi.',
    ]);
}

public function uploadReviewAttachment(Request $r, int $id)
{
    $review = CustomerReview::firstOrCreate(
        ['id_verification' => $id],
        ['review_tanggal' => now()]
    );

    $r->validate([
        'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar'
    ]);

    $file = $r->file('file');
    $path = $file->store("customer_verifications/{$id}/review", 'public');
    $ori  = $file->getClientOriginalName();

    // pakai tabel yang benar
    $next = ((int) DB::table('customer_review_attchment')
        ->where('id_review', $review->id_review)
        ->where('id_verification', $id)
        ->max('no_urut')) + 1;

        \Log::info('UPL-ATTCH', [
            'id_ver' => $id,
            'path'   => $path,
            'ori'    => $ori,
            'next'   => $next,
          ]);
          

    DB::table('customer_review_attchment')->insert([
        'id_review'         => $review->id_review,
        'id_verification'   => $id,
        'no_urut'           => $next,
        'review_attach'     => $path,
        'review_attach_ori' => $ori,
    ]);

    return response()->json([
        'no_urut' => $next,
        'name'    => $ori,
        'path'    => $path,
        'url'     => Storage::disk('public')->url($path),
    ]);
}



public function deleteReviewAttachment(int $id, int $no)
{
    $review = CustomerReview::where('id_verification', $id)->firstOrFail();
    $att = CustomerReviewAttachment::where('id_review', $review->id_review)
            ->where('id_verification', $id)
            ->where('no_urut', $no)
            ->firstOrFail();

    // hapus file fisik (opsional)
    if ($att->review_attach) {
        Storage::disk('public')->delete($att->review_attach);
    }
    $att->delete();

    return response()->json(['ok'=>true]);
}

public function approve(Request $r, int $id)
{
    $cv = CustomerVerification::findOrFail($id);
    $cv->update(['disposisi_result' => 1]); // tandai disetujui
    return response()->json(['ok' => true, 'message' => 'Persetujuan dikirim.']);
}

public function reviewAdminIndex(Request $r)
{
    $per = (int) $r->query('per_page', 25);
    $q   = trim((string) $r->query('q', ''));

    $rows = CustomerVerification::query()
        ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
        ->where('is_active', 1)
        ->where('is_reviewed', 1)
        // ->where('disposisi_result', 1) // HANYA tahap admin
        ->when($q !== '', function ($w) use ($q) {
            $w->whereHas('customer', function ($c) use ($q) {
                $c->where('nama_perusahaan', 'like', "%{$q}%")
                  ->orWhere('alamat_perusahaan', 'like', "%{$q}%")
                  ->orWhere('kode_pelanggan', 'like', "%{$q}%");
            });
        })
        ->orderByDesc('id_verification')
        ->paginate($per);

    return response()->json([
        'data'         => $rows->items(),
        'current_page' => $rows->currentPage(),
        'last_page'    => $rows->lastPage(),
        'total'        => $rows->total(),
    ]);
}

public function reviewAdminStats()
{
    // jumlah item di antrean admin (is_reviewed=1 & disposisi=1)
    $queue = CustomerVerification::query()
        ->where('is_active', 1)
        ->where('is_reviewed', 1)
        ->where('disposisi_result', 1)
        ->count();

    return response()->json(['queue' => $queue]);
}

// OPSIONAL: untuk melanjutkan tahap (misal dari admin -> logistik)
public function setDisposisi(Request $r, int $id)
{
    $data = $r->validate([
        'to'   => 'required|integer|in:1,2,3,4', // 1=Admin,2=Logistik,3=BM,4=OM
    ]);

    $row = CustomerVerification::findOrFail($id);
    $row->update(['disposisi_result' => $data['to']]);

    return response()->json(['ok' => true, 'disposisi_result' => (int)$data['to']]);
}
public function getEvaluation(int $id)
{
    $cv  = \App\Models\CustomerVerification::findOrFail($id);
    $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];

    return response()->json([
        'evaluation' => $kyc['evaluation'] ?? [
            'top'                    => 'CREDIT 30 days After Invoice Receive',
            'potential_volume'       => null,
            'potential_unit'         => 'Liter',
            'credit_limit_proposed'  => null,
            'jenis_data'             => 'Sebelum Persetujuan Komite',
            'financial_review'       => '',
        ],
        // kirim juga disposisi agar FE bisa lock
        'disposisi_result' => (int) ($cv->disposisi_result ?? 0),
    ]);
}

public function saveEvaluation(Request $r, int $id)
{
    // Validasi sesuai payload FE (approval di dalam form)
    $payload = $r->validate([
        'form'                               => ['required','array'],
        'form.top_text'                      => ['nullable','string'],
        'form.potential_volume'              => ['nullable','string'],
        'form.jenis_data'                    => ['required','in:SEBELUM,SETELAH'],
        'form.financial_review'              => ['nullable','string'],

        'form.evaluation_numbers'            => ['nullable','array'],
        'form.evaluation_numbers.*'          => ['nullable','string'],

        'form.kyc_rows'                      => ['nullable','array'],
        'form.kyc_rows.*.label'              => ['nullable','string'],
        'form.kyc_rows.*.name'               => ['nullable','string'],
        'form.kyc_rows.*.path'               => ['nullable','string'],
        'form.kyc_rows.*.url'                => ['nullable','string'],

        'form.approval'                      => ['nullable','array'],
        'form.approval.approval_credit_limit'=> ['nullable','string'],
        'form.approval.payment_type'         => ['nullable','in:CREDIT,CASH'],
        'form.approval.top_days'             => ['nullable','string'],
        'form.approval.top_basis'            => ['nullable','string'],
        'form.approval.group_company'        => ['nullable','string'],

        'form.approval.docs'                 => ['nullable','array'],
        'form.approval.docs.customer_db'     => ['boolean'],
        'form.approval.docs.siup'            => ['boolean'],
        'form.approval.docs.notarial'        => ['boolean'],
        'form.approval.docs.lcr'             => ['boolean'],
        'form.approval.docs.npwp'            => ['boolean'],
        'form.approval.docs.finstat'         => ['boolean'],
        'form.approval.docs.top'             => ['boolean'],
        'form.approval.docs.customer_review' => ['boolean'],
        'form.approval.docs.others'          => ['boolean'],
        'form.approval.docs_others_text'     => ['nullable','string'],

        'form.approval.other_document'       => ['nullable','string'],
        'form.approval.logistik_summary'     => ['nullable','string'],
        'form.approval.logistik_result'      => ['nullable','string'],
        'form.approval.assessment_result'    => ['nullable','string'],
    ]);

    $form     = $payload['form'];
    $approval = $form['approval'] ?? [];

    // --- Sanitasi seperti script lama ---
    $creditLimitRaw = $approval['approval_credit_limit'] ?? null;
    $creditLimit    = is_null($creditLimitRaw) ? null : (int) preg_replace('/\D+/', '', (string) $creditLimitRaw);

    $summaryRaw = (string) ($form['financial_review'] ?? '');
    $summary    = nl2br(e($summaryRaw), false);

    $dokumenLainnya = e((string) ($approval['other_document'] ?? ''));

    // --- finance_data (nomor & checklist dokumen) ---
    $arrData = [];
    foreach ((array) ($form['evaluation_numbers'] ?? []) as $no) {
        if ($no !== null && $no !== '') {
            $arrData[] = ['nomor' => e((string) $no)];
        }
    }
    $docs = (array) ($approval['docs'] ?? []);
    if (!empty($docs)) {
        $checked = [];
        foreach ($docs as $k => $v) if ($v) $checked[] = $k;
        if ($checked) $arrData[] = ['nomor' => implode(',', $checked)];
        if ($dokumenLainnya !== '') $arrData[] = $dokumenLainnya;
    }

    $incomingKyc = (array) ($form['kyc_rows'] ?? []);

    DB::transaction(function () use ($id, $form, $approval, $arrData, $incomingKyc, $summary, $creditLimit) {

        // Kunci baris
        $cv = \App\Models\CustomerVerification::lockForUpdate()->findOrFail($id);

        // Hapus file KYC lama yang tidak dipertahankan
        $oldKyc    = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
        $keepPaths = collect($incomingKyc)->pluck('path')->filter()->values()->all();
        foreach ($oldKyc as $row) {
            $oldPath = $row['filenya'] ?? ($row['path'] ?? null);
            if ($oldPath && !in_array($oldPath, $keepPaths, true)) {
                \Storage::disk('public')->delete($oldPath);
            }
        }

        // Normalisasi KYC simpan
        $kycSaved = [];
        foreach ($incomingKyc as $idx => $row) {
            $kycSaved[$idx] = [
                'id_detail'       => $idx,
                'nama_file'       => e((string) ($row['label'] ?? $row['name'] ?? '')),
                'filenya'         => (string) ($row['path'] ?? ''),
                'file_upload_ori' => (string) ($row['name'] ?? basename($row['path'] ?? '')),
            ];
        }

        // Map jenis data (tetap disimpan kalau perlu)
        $jenisDataInt = ($form['jenis_data'] ?? 'SEBELUM') === 'SETELAH' ? 2 : 1;

        // === PERMINTAANMU: paksa nilai ini saat berhasil simpan ===
        $financeResult = 1;   // <- selalu 1
        $disp          = 2;   // <- selalu 2

        // Update verification
        $cv->update([
            'finance_data'        => json_encode($arrData, JSON_UNESCAPED_UNICODE),
            'jenis_datanya'       => $jenisDataInt,
            'finance_summary'     => $summary,
            'finance_result'      => $financeResult,            // 1
            'finance_tgl_proses'  => now(),
            'finance_pic'         => auth()->user()->name ?? null,
            'finance_data_kyc'    => json_encode($kycSaved, JSON_UNESCAPED_UNICODE),
            'disposisi_result'    => $disp,                     // 2
        ]);

        // Jika SETELAH komite, sinkron ke customers (opsional, tetap dipertahankan)
        if ($jenisDataInt === 2) {
            $payType  = $approval['payment_type'] ?? null; // CREDIT/CASH
            $topDays  = $approval['top_days']   ?? null;
            $topBasis = $approval['top_basis']  ?? null;

            $updateCustomer = [
                'dokumen_lainnya' => $approval['other_document'] ?? null,
                'credit_limit'    => $creditLimit,
                'jenis_payment'   => $payType,
            ];

            if ($payType === 'CREDIT') {
                $updateCustomer['top_payment'] = $topDays;
                $updateCustomer['jenis_net']   = $topBasis;
            } else {
                $updateCustomer['top_payment'] = null;
                $updateCustomer['jenis_net']   = null;
            }

            \DB::table('customers')
                ->where('id_customer', $cv->id_customer)
                ->update($updateCustomer);
        }
    });

    return response()->json(['ok' => true]);
}

public function reviewLogistikIndex(Request $r)
{
    $perPage = (int)($r->get('per_page', 25));
    $q       = trim((string)$r->get('q', ''));

    $qry = CustomerVerification::query()
        ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
        ->select([
            'id_verification',
            'id_customer',
            'token_verification',
            'is_reviewed',
            'disposisi_result',
        ])
        ->where('is_reviewed', 1)
        ->where('disposisi_result', 2); // tahap logistik

    if ($q !== '') {
        $qry->whereHas('customer', function($s) use ($q) {
            $s->where('nama_perusahaan', 'ilike', "%{$q}%")
              ->orWhere('kode_pelanggan', 'ilike', "%{$q}%")
              ->orWhere('alamat_perusahaan', 'ilike', "%{$q}%");
        });
    }

    $rows = $qry->orderByDesc('id_verification')->paginate($perPage);
    return response()->json($rows);
}

public function reviewLogistikStats()
{
    $queue = CustomerVerification::where('is_reviewed', 1)
        ->where('disposisi_result', 2)
        ->count();

    return response()->json(['queue' => $queue]);
}

public function logistikShow(int $id)
{
    $cv = CustomerVerification::with('customer')->findOrFail($id);

    // business_type ambil dari legal/corporate kalau ada
    $legal = json_decode($cv->legal_data ?? '{}', true);
    $businessType = $legal['corporate']['ownership'] ?? ($legal['corporate']['tipe_bisnis_text'] ?? '-');

    return response()->json([
        'customer'      => $cv->customer ? [
            'nama_perusahaan'   => $cv->customer->nama_perusahaan,
            'alamat_perusahaan' => $cv->customer->alamat_perusahaan,
        ] : null,
        'business_type' => $businessType,
        'form' => [
            'logistik_summary'  => $cv->logistik_summary ?? '',
            'logistik_result'   => $cv->logistik_result_text ?? ($cv->logistik_result ?? ''), // sesuaikan nama kolom Anda
            'assessment_result' => $cv->assessment_result ?? '',
        ],
    ]);
}

public function logistikSave(Request $r, int $id)
{
    $data = $r->validate([
        'logistik_summary'  => ['nullable','string'],
        'logistik_result'   => ['nullable','string'],
        'assessment_result' => ['nullable','string','in:Supply Delivery,Supply Delivery With Note,Revised and Resubmitted'],
    ]);

    $cv = CustomerVerification::findOrFail($id);

    // Simpan hanya kolom logistik; kolom lain tetap read-only
    $cv->update([
        'logistik_summary'   => $data['logistik_summary']  ?? '',
        // jika Anda punya kolom string khusus, pakai 'logistik_result_text'
        // kalau memang kolomnya bernama 'logistik_result' (tipe string), gunakan itu.
        'logistik_result'    => 1,
        'disposisi_result'   => 3,
        'logistik_tgl_proses'=> now(),
        'logistik_pic'       => auth()->user()->name ?? null,
        'assessment_result'  => $data['assessment_result'] ?? null,
    ]);

    return response()->json(['ok' => true]);
}

public function reviewBmIndex(Request $r)
{
    $q       = trim((string) $r->query('q', ''));
    $perPage = (int) ($r->query('per_page', 25));

    $rows = CustomerVerification::query()
        ->with(['customer']) // pastikan relasi ada
        ->where('is_reviewed', 1)
        ->where('disposisi_result', 3) // antrean BM
        ->when($q, function($qq) use ($q){
            $qq->whereHas('customer', function($c) use ($q){
                $c->where('nama_perusahaan','ilike',"%{$q}%")
                  ->orWhere('alamat_perusahaan','ilike',"%{$q}%")
                  ->orWhere('kode_pelanggan','ilike',"%{$q}%");
            });
        })
        ->orderByDesc('id_verification')
        ->paginate($perPage);

    return response()->json($rows);
}

public function reviewBmStats()
{
    $queue = CustomerVerification::where('is_reviewed',1)->where('disposisi_result',3)->count();
    return response()->json(['queue'=>$queue]);
}

/**
 * Simpan verifikasi BM:
 * - bm_notes: catatan BM
 * - bm_decision: APPROVE / REVISE / REJECT (opsional)
 * Default: APPROVE -> bm_result=1, disposisi=4 (OM)
 */
public function bmVerify(Request $r, int $id)
{
    $data = $r->validate([
        'notes'    => ['nullable','string'],
        'decision' => ['nullable','in:APPROVE,REVISE,REJECT'],
    ]);

    return DB::transaction(function () use ($id, $data) {
        /** @var \App\Models\CustomerVerification $cv */
        $cv = CustomerVerification::lockForUpdate()->findOrFail($id);

        // default approval
        $decision = $data['decision'] ?? 'APPROVE';

        $updates = [
            'bm_notes'       => $data['notes'] ?? null,
            'bm_decision'    => $decision,
            'bm_tgl_proses'  => now(),
            'bm_pic'         => auth()->user()->name ?? null,
        ];

        if ($decision === 'APPROVE') {
            $updates['sm_result']       = 1;  // OK
            $updates['disposisi_result']= 4;  // ke OM
        } elseif ($decision === 'REVISE') {
            // kembalikan ke Logistik untuk revisi
            $updates['sm_result']       = 0;
            $updates['disposisi_result']= 2;
        } else { // REJECT
            $updates['sm_result']       = 0;
            // tetap di BM untuk diproses/arsip (atau set ke 0 sesuai kebijakan)
            $updates['disposisi_result']= 3;
        }

        $cv->update($updates);

        return response()->json(['ok'=>true]);
    });
}


public function reviewOmStats()
{
    $queue = \App\Models\CustomerVerification::query()
        ->where('is_reviewed', 1)
        ->where('disposisi_result', 4) // OM
        ->where('is_active', 1)
        ->count();

    return response()->json(['queue' => (int)$queue]);
}

// ==== OM INDEX ====
public function reviewOmIndex(Request $r)
{
    $per = (int) $r->query('per_page', 25);
    $q   = trim((string) $r->query('q', ''));

    $rows = \App\Models\CustomerVerification::query()
        ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
        ->where('is_reviewed', 1)
        ->where('disposisi_result', 4) // OM
        ->where('is_active', 1)
        ->when($q !== '', function($w) use ($q){
            $w->whereHas('customer', function($c) use ($q){
                $c->where('nama_perusahaan','like',"%{$q}%")
                  ->orWhere('alamat_perusahaan','like',"%{$q}%")
                  ->orWhere('kode_pelanggan','like',"%{$q}%");
            });
        })
        ->orderByDesc('id_verification')
        ->paginate($per);

    return response()->json([
        'data'         => $rows->items(),
        'current_page' => $rows->currentPage(),
        'last_page'    => $rows->lastPage(),
        'total'        => $rows->total(),
    ]);
}

// ==== OM VERIFY (simpan keputusan OM) ====
public function omVerify(Request $r, int $id)
{
    $data = $r->validate([
        'result'  => ['required','integer','in:0,1'], // 1=approve, 0=reject
        'summary' => ['nullable','string'],
    ]);

    $cv = \App\Models\CustomerVerification::findOrFail($id);

    $update = [
        'om_result'     => (int) $data['result'],
        'om_summary'    => $data['summary'] ?? null,
        'om_tgl_proses' => now(),
        'om_pic'        => auth()->user()->name ?? null,
        // tetap di disposisi OM (4). Jika ingin "selesai" bisa set ke 0.
        'disposisi_result' => 5,
    ];

    // opsional: set approved bila OM approve
    if ((int)$data['result'] === 1) {
        $update['is_approved']      = 1;
        $update['tanggal_approved'] = now();
        $update['role_approve']     = 4; // OM
    }

    $cv->update($update);

    return response()->json(['ok' => true]);
}





}
