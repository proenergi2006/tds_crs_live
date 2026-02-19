<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerLogistik;
use App\Models\CustomerPayment;
use App\Models\CustomerAdminArnya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    // GET /api/customers
    public function index(Request $request)
    {
        // user yang login
        $userId = $request->user()->id;

        $q = Customer::query()
            ->with(['user','provinsi','kabupaten','cabang'])
            ->where('id_user', $userId)
            ->withExists(['lcr as has_lcr'])
            ->withCount(['penawarans as jumlah_penawaran']);

        if ($s = $request->query('search')) {
            $q->where(function($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('nama_perusahaan', 'like', "%{$s}%");
            });
        }

        $perPage = $request->query('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    // POST /api/customers
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_user'           => 'sometimes|nullable|exists:users,id', // akan diabaikan
            'email'             => 'required|email|unique:customers,email',
            'id_provinsi'       => 'required|exists:provinsis,id_provinsi',
            'id_kabupaten'      => 'required|exists:kabupatens,id_kabupaten',
            'postal_code'       => 'nullable|string|max:20',
            'telepon'           => 'nullable|string|max:30',
            'jenis_customer'    => 'nullable|string|max:50',
            'nama_perusahaan'   => 'nullable|string|max:255',
            'alamat_perusahaan' => 'nullable|string',
            'fax'               => 'nullable|string|max:30',
            'is_active'         => 'sometimes|boolean',
        ]);

        // FORCE user login
        $data['id_user']      = $request->user()->id;
        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;

        if (!empty($data['nama_perusahaan'])) {
            $data['nama_perusahaan'] = mb_strtoupper(trim($data['nama_perusahaan']), 'UTF-8');
        }

        $customer = null;

        DB::transaction(function () use (&$customer, $data) {
            // 1) customer utama
            $customer = Customer::create($data);

            // 2) seed contact
            CustomerContact::firstOrCreate(
                ['id_customer' => $customer->id_customer],
                [
                    'pic_decision_telp'   => '',
                    'pic_decision_mobile' => '',
                    'pic_ordering_telp'   => '',
                    'pic_ordering_mobile' => '',
                    'pic_billing_telp'    => '',
                    'pic_billing_mobile'  => '',
                    'pic_invoice_telp'    => '',
                    'pic_invoice_mobile'  => '',
                ]
            );

            // 3) seed logistik
            CustomerLogistik::firstOrCreate(
                ['id_customer' => $customer->id_customer],
                [
                    'logistik_area'       => '',
                    'logistik_bisnis'     => '',
                    'logistik_env'        => 0,
                    'logistik_storage'    => 0,
                    'logistik_hour'       => 0,
                    'logistik_volume'     => 0,
                    'logistik_quality'    => 0,
                    'logistik_truck'      => 0,
                    'desc_stor_fac'       => '',
                    'desc_condition'      => '',
                ]
            );

            // 4) seed payment
            CustomerPayment::firstOrCreate(
                ['id_customer' => $customer->id_customer],
                [
                    'telp_billing'        => '',
                    'fax_billing'         => '',
                    'payment_schedule'    => 0,
                    'payment_method'      => 0,
                    'invoice'             => 0,
                    'ket_extra'           => '',
                ]
            );

            // 5) seed admin_arnya
            CustomerAdminArnya::firstOrCreate(
                ['id_customer' => $customer->id_customer],
                [
                    'not_yet'     => 0,
                    'ov_up_07'    => 0,
                    'ov_under_30' => 0,
                    'ov_under_60' => 0,
                    'ov_under_90' => 0,
                    'ov_up_90'    => 0,
                ]
            );
        });

        return response()->json($customer, 201);
    }

    // GET /api/customers/{id}
    public function show(Request $request, $id)
    {
        $customer = Customer::with(['user', 'provinsi', 'kabupaten'])
                            ->findOrFail($id);

        // (opsional) batasi akses
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($customer);
    }

    // PUT/PATCH /api/customers/{id}
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_user'           => 'sometimes|nullable|exists:users,id', // diabaikan
            'email'             => "required|email|unique:customers,email,{$id},id_customer",
            'id_provinsi'       => 'required|exists:provinsis,id_provinsi',
            'id_kabupaten'      => 'required|exists:kabupatens,id_kabupaten',
            'postal_code'       => 'nullable|string|max:20',
            'telepon'           => 'nullable|string|max:30',
            'jenis_customer'    => 'nullable|string|max:50',
            'nama_perusahaan'   => 'nullable|string|max:255',
            'alamat_perusahaan' => 'nullable|string',
            'fax'               => 'nullable|string|max:30',
            'is_active'         => 'sometimes|boolean',
        ]);

        $customer = Customer::findOrFail($id);

        // ownership check (opsional tapi direkomendasikan)
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // FORCE user login
        $data['id_user']         = $request->user()->id;
        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;

        if (!empty($data['nama_perusahaan'])) {
            $data['nama_perusahaan'] = mb_strtoupper(trim($data['nama_perusahaan']), 'UTF-8');
        }

        $customer->update($data);

        return response()->json($customer);
    }

    // DELETE /api/customers/{id}
    public function destroy(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // ownership check
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer->delete();
        return response()->json(null, 204);
    }
}
