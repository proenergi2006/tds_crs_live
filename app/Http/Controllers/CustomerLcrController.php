<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomerLcr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerLcrController extends Controller
{
   // Controller
private function rules(bool $forUpdate = false): array
{
    return [
        // create: required, update: boleh tidak dikirim (partial)
        'id_customer' => $forUpdate ? ['sometimes','integer'] : ['required','integer'],

        'id_cabang'   => ['nullable','integer'],
        'id_wilayah'  => ['nullable','integer'],
        'id_wil_oa'   => ['nullable','integer'],
        'alamat_survey' => ['nullable','string'],
        'prov_survey'   => ['nullable','integer'],
        'kab_survey'    => ['nullable','integer'],
        'telp_survey'   => ['nullable','string','max:50'],
        'fax_survey'    => ['nullable','string','max:50'],
        'tgl_survey'    => ['nullable','date'],

        'nama_surveyor' => ['nullable','array'],
        'review'        => ['nullable','string'],
        'jenis_usaha'   => ['nullable','string','max:100'],
        'website'       => ['nullable','string','max:191'],
        'hasilsurv'     => ['nullable','array'],
        'produkvol'     => ['nullable','array'],
        'picustomer'    => ['nullable','array'],

        'alat_ukur'     => ['nullable','string','max:100'],
        'toleransi'     => ['nullable','string','max:50'],
        'kompetitor'    => ['nullable','array'],
        'jam_operasional' => ['nullable','array'],

        'logistik_summary' => ['nullable','string'],
        'logistik_result'  => ['nullable','integer','in:0'],
        'logistik_tanggal' => ['nullable','date'],
        'logistik_pic'     => ['nullable','string','max:100'],

        'sm_summary' => ['nullable','string'],
        'sm_result'  => ['nullable','integer','in:0'],
        'sm_tanggal' => ['nullable','date'],
        'sm_pic'     => ['nullable','string','max:100'],

        'flag_disposisi' => ['nullable','integer','in:1'],
        'flag_approval'  => ['nullable','integer'],
        'tgl_approval'   => ['nullable','date'],

        'tangki'          => ['nullable','array'],
        'pendukung'       => ['nullable','array'],
        'quantity_tangki' => ['nullable','array'],
        'quality_tangki'  => ['nullable','array'],
        'catatan_tangki'  => ['nullable','string'],

        'kapal'           => ['nullable','array'],
        'jetty'           => ['nullable','array'],
        'quantity_kapal'  => ['nullable','array'],
        'quality_kapal'   => ['nullable','array'],
        'catatan_kapal'   => ['nullable','string'],

        'penjelasan_bongkar' => ['nullable','string'],

        'latitude_lokasi' => ['nullable','numeric'],
        'longitude_lokasi'=> ['nullable','numeric'],
        'link_google_maps'=> ['nullable','string'],

        'jarak_depot'   => ['nullable','string','max:50'],
        'max_truk'      => ['nullable','string','max:50'],
        'lsm_portal'    => ['nullable','string','max:50'],
        'min_vol_kirim' => ['nullable','string','max:50'],

        'rute_lokasi' => ['nullable','string'],
        'note_lokasi' => ['nullable','string'],
        'jenis_usaha_lain' => ['nullable','string'],

        // ====== MEDIA (array of objects) ======
        'layout_lokasi'                 => ['nullable','array'],
        'layout_lokasi.*.path'          => ['required','string'],
        'layout_lokasi.*.url'           => ['required','string'],
        'layout_lokasi.*.caption'       => ['nullable','string','max:255'],

        'layout_bongkar'                => ['nullable','array'],
        'layout_bongkar.*.path'         => ['required','string'],
        'layout_bongkar.*.url'          => ['required','string'],
        'layout_bongkar.*.caption'      => ['nullable','string','max:255'],

        'kondisi_jalan'                 => ['nullable','array'],
        'kondisi_jalan.*.path'          => ['required','string'],
        'kondisi_jalan.*.url'           => ['required','string'],
        'kondisi_jalan.*.caption'       => ['nullable','string','max:255'],

        'kantor_perusahaan'             => ['nullable','array'],
        'kantor_perusahaan.*.path'      => ['required','string'],
        'kantor_perusahaan.*.url'       => ['required','string'],
        'kantor_perusahaan.*.caption'   => ['nullable','string','max:255'],

        'fasilitas_storage'             => ['nullable','array'],
        'fasilitas_storage.*.path'      => ['required','string'],
        'fasilitas_storage.*.url'       => ['required','string'],
        'fasilitas_storage.*.caption'   => ['nullable','string','max:255'],

        'inlet_pipa'                    => ['nullable','array'],
        'inlet_pipa.*.path'             => ['required','string'],
        'inlet_pipa.*.url'              => ['required','string'],
        'inlet_pipa.*.caption'          => ['nullable','string','max:255'],

        'alat_ukur_gambar'              => ['nullable','array'],
        'alat_ukur_gambar.*.path'       => ['required','string'],
        'alat_ukur_gambar.*.url'        => ['required','string'],
        'alat_ukur_gambar.*.caption'    => ['nullable','string','max:255'],

        'media_datar'                   => ['nullable','array'],
        'media_datar.*.path'            => ['required','string'],
        'media_datar.*.url'             => ['required','string'],
        'media_datar.*.caption'         => ['nullable','string','max:255'],

        'keterangan_lain'               => ['nullable','array'],
        'keterangan_lain.*.path'        => ['required','string'],
        'keterangan_lain.*.url'         => ['required','string'],
        'keterangan_lain.*.caption'     => ['nullable','string','max:255'],
    ];
}


    private function messages(): array
    {
        return [
            'id_customer.required' => 'Customer wajib dipilih.',
        ];
    }

    private function validateRequest(Request $request, bool $forUpdate = false)
{
    $v = Validator::make($request->all(), $this->rules($forUpdate), $this->messages());
    if ($v->fails()) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $v->errors(),
        ], 422);
    }
    return $v->validated();
}

    /** List + search */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $q = trim((string) $request->query('q', ''));
    
        // <-- eager load relasi customer, hanya ambil kolom yang diperlukan
        $query = CustomerLcr::with(['customer:id_customer,nama_perusahaan']);
    
        if ($q !== '') {
            // filter ke kolom LCR
            $query->where(function ($w) use ($q) {
                // kalau pakai PostgreSQL bisa ganti 'like' => 'ilike' (case-insensitive)
                $w->where('alamat_survey', 'like', "%{$q}%")
                  ->orWhere('jenis_usaha', 'like', "%{$q}%")
                  ->orWhere('review', 'like', "%{$q}%");
            })
            // filter juga ke relasi customer (opsional)
            ->orWhereHas('customer', function ($w) use ($q) {
                $w->where('nama_perusahaan', 'like', "%{$q}%")
                  ->orWhere('kode', 'like', "%{$q}%"); // hapus baris ini kalau kolom 'kode' tidak ada
            });
        }
    
        return $query->orderByDesc('created_time')->paginate($perPage);
    }
    

    /** Create */
    public function store(Request $request)
    {

        $request->merge([
            'logistik_result' => 0,
            'flag_disposisi'  => 1,
        ]);
        $validated = $this->validateRequest($request);
        if ($validated instanceof \Illuminate\Http\JsonResponse) return $validated;

        $payload = $validated;

        $payload['logistik_result'] = 0;
    $payload['flag_disposisi']  = 1;

// kolom id_cabang tidak ada di tabel customer_lcr ⇒ buang dari payload
        unset($payload['id_cabang']);
        $payload['created_time'] = now();
        $payload['created_ip']   = $request->ip();
        $payload['created_by']   = optional($request->user())->name ?? 'system';

        $lcr = null;
        DB::transaction(function () use ($payload, &$lcr) {
            $lcr = CustomerLcr::create($payload);
        });

        return response()->json($lcr, 201);
    }

    /** Detail */
    public function show(CustomerLcr $customerLcr)
    {
        return $customerLcr;
    }

    /** Update */
    public function update(Request $request, CustomerLcr $customerLcr)
    {
        // ← penting: TRUE supaya rules “sometimes” berlaku
        $validated = $this->validateRequest($request, true);
        if ($validated instanceof \Illuminate\Http\JsonResponse) return $validated;
    
        $payload = $validated;
        unset($payload['id_cabang']);
    
        $payload['lastupdate_time'] = now();
        $payload['lastupdate_ip']   = $request->ip();
        $payload['lastupdate_by']   = optional($request->user())->name ?? 'system';
    
        DB::transaction(function () use ($customerLcr, $payload) {
            $customerLcr->update($payload);
        });
    
        return $customerLcr->fresh();
    }

    /** Delete */
    public function destroy(CustomerLcr $customerLcr)
    {
        $customerLcr->delete();
        return response()->noContent();
    }


    public function uploadImage(Request $request)
{
    $data = $request->validate([
        'file' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'],
    ]);

    $path = $request->file('file')->store('lcr', 'public');

    return response()->json([
        'path' => $path,
        'url'  => asset('storage/'.$path),
        'name' => $request->file('file')->getClientOriginalName(),
        'size' => $request->file('file')->getSize(),
    ]);
}


    public function indexLogistik(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $q       = trim((string) $request->query('q', ''));
        $status  = $request->query('status', 'all');

        $query = CustomerLcr::query()
            ->with(['customer:id_customer,nama_perusahaan']);

        // filter status berdasar flag_disposisi
        $query->where(function ($w) use ($status) {
            switch ($status) {
                case 'menunggu':  $w->where('flag_disposisi', 1); break;
                case 'disetujui': $w->where('flag_disposisi', 2); break;
                case 'ditolak':   $w->where('flag_disposisi', 3); break;
                case 'all':
                default: /* no filter */ break;
            }
        });

        // pencarian (dibungkus dalam where untuk tidak "men-cancel" filter status)
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('alamat_survey', 'like', "%{$q}%")
                  ->orWhere('jenis_usaha', 'like', "%{$q}%")
                  ->orWhere('review', 'like', "%{$q}%")
                  ->orWhereHas('customer', function ($c) use ($q) {
                      $c->where('nama_perusahaan', 'like', "%{$q}%");
                  });
            });
        }

        return $query->orderByDesc('created_time')->paginate($perPage);
    }

    public function setFlag(Request $request, CustomerLcr $customerLcr)
    {
        $data = $request->validate([
            'flag_disposisi'   => ['required','integer','in:1,2,3'], // 1=Menunggu, 2=Approved, 3=Rejected
            'logistik_summary' => ['nullable','string'],
        ]);
    
        // field umum yang selalu diupdate saat verifikasi logistik
        $updates = [
            'flag_disposisi'   => $data['flag_disposisi'],
            'logistik_result'  => 1, // kalau memang mau selalu 1; kalau mau dinamis lihat catatan di bawah
            'logistik_summary' => $data['logistik_summary'] ?? null,
            'logistik_tanggal' => now(),
            'logistik_pic'     => optional($request->user())->name ?? 'system',
            'lastupdate_time'  => now(),
            'lastupdate_ip'    => $request->ip(),
            'lastupdate_by'    => optional($request->user())->name ?? 'system',
        ];
    
        // hanya saat DISETUJUI logistik → set approval & tgl_approval
        if ((int)$data['flag_disposisi'] === 2) {
            $updates += [
                'flag_approval' => 1,
                'tgl_approval'  => now(),
            ];
        }
       
    
        $customerLcr->update($updates);
    
        return $customerLcr->fresh()->loadMissing('customer:id_customer,nama_perusahaan');
    }
    

    /**
     * Reset ke Menunggu (flag=1)
     */
    public function resetFlag(CustomerLcr $customerLcr, Request $request)
    {
        $customerLcr->update([
            'flag_disposisi'  => 1,
            'logistik_summary'=> null,
            'lastupdate_time' => now(),
            'lastupdate_ip'   => $request->ip(),
            'lastupdate_by'   => optional($request->user())->name ?? 'system',
        ]);

        return $customerLcr->fresh()->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function showLogistik($id)
    {
        $row = \App\Models\CustomerLcr::with([
            'customer:id_customer,nama_perusahaan',
            'wilayahAngkut:id,destinasi',
        ])->findOrFail($id);
    
        return response()->json($row);
    }
    
    // (opsional) kalau mau pakai show() yg lama juga bisa diubah sedikit:
    // public function show(CustomerLcr $customerLcr)
    // {
    //     $customerLcr->load([
    //         'customer:id_customer,nama_perusahaan,kode',
    //         'wilayahAngkut:id,destinasi',
    //     ]);
    //     return $customerLcr;
    // }

    

}
