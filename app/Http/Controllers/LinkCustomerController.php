<?php
// app/Http/Controllers/LinkCustomerController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class LinkCustomerController extends Controller
{
    /**
     * GET /api/link-customers
     * List semua customer dengan need_update = 1 (dibatasi user login).
     * Support ?search= & ?per_page=
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $search  = trim((string) $request->query('search', ''));

        $q = Customer::query()
            ->with(['cabang'])                      // cabang invoice
            ->where('need_update', 1)
            ->where('is_generated_link', 0)
            ->when($request->user(), function ($w) use ($request) {
                // kalau memang data customer dibatasi ke user pembuatnya
                $w->where('id_user', $request->user()->id);
            });

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('nama_perusahaan', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('alamat_perusahaan', 'like', "%{$search}%");
            });
        }

        return $q->orderBy('nama_perusahaan')->paginate($perPage);
    }

    /**
     * POST /api/link-customers/{customer}/generate
     * Buat (atau gunakan yang masih aktif) link verifikasi untuk 1 customer.
     * Return token + full link.
     */
    public function generate(Request $request, Customer $customer)
    {
        // kalau mau batasi: hanya untuk customer yang need_update=1
        // if ((int)$customer->need_update !== 1) {
        //     return response()->json(['message' => 'Customer tidak perlu link.'], 422);
        // }

        // cek kalau sudah ada yang aktif → pakai itu saja supaya tidak dobel
        $existing = CustomerVerification::where('id_customer', $customer->id_customer)
            ->where('is_active', 1)
            ->latest('id_verification')
            ->first();

        if ($existing) {

            if ((int)($customer->is_generated_link ?? 0) === 0) {
                $customer->forceFill([
                    'is_generated_link' => 1,
                    'lastupdate_time'   => now(),
                    'lastupdate_by'     => optional($request->user())->name ?? 'system',
                ])->save();
            }

            $link = rtrim(config('app.frontend_url', config('app.url')), '/')
                . '/verify/' . $existing->token_verification;

            return response()->json([
                'already_exists' => true,
                'verification'   => $existing,
                'link'           => $link,
            ]);
        }

        // generate token 17 char (huruf/angka huruf besar)
        do {
            $token = Str::upper(Str::random(17));
        } while (CustomerVerification::where('token_verification', $token)->exists());

        // Buat baris minimal – kolom TEXT NOT NULL diisi '' agar lolos constraint
        $cv = CustomerVerification::create([
            'id_customer'        => $customer->id_customer,
            'token_verification' => $token,
            'is_evaluated'       => 0,
            'is_reviewed'        => 0,
            'is_active'          => 1,

            'legal_data'         => '',
            'legal_summary'      => '',
            'legal_result'       => 0,
            'legal_tgl_proses'   => null,
            'legal_pic'          => '',

            'finance_data'       => '',
            'finance_summary'    => '',
            'finance_result'     => 0,
            'finance_tgl_proses' => null,
            'finance_pic'        => '',

            'logistik_data'      => '',
            'logistik_summary'   => '',
            'logistik_result'    => 0,
            'logistik_tgl_proses'=> null,
            'logistik_pic'       => '',

            'sm_summary'         => '',
            'sm_result'          => 0,
            'sm_tgl_proses'      => null,
            'sm_pic'             => '',

            'om_summary'         => '',
            'om_result'          => 0,
            'om_tgl_proses'      => null,
            'om_pic'             => '',

            'cfo_summary'        => '',
            'cfo_result'         => 0,
            'cfo_tgl_proses'     => null,
            'cfo_pic'            => '',

            'ceo_summary'        => '',
            'ceo_result'         => 0,
            'ceo_tgl_proses'     => null,
            'ceo_pic'            => '',

            'disposisi_result'   => 0,
            'is_approved'        => 0,
            'role_approve'       => null,
            'tanggal_approved'   => null,
            'jenis_datanya'      => 0,
            'finance_data_kyc'   => null,
        ]);

        $link = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/verify/' . $token;

        return response()->json([
            'already_exists' => false,
            'verification'   => $cv,
            'link'           => $link,
        ], 201);
    }
}
