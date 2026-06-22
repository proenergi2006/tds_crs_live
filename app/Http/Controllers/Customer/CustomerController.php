<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAdminArnya;
use App\Models\CustomerContact;
use App\Models\CustomerLogistik;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::query()
            ->with(['user', 'provinsi', 'kabupaten', 'cabang'])
            ->where('id_user', $request->user()->id)
            ->withExists(['lcr as has_lcr'])
            ->withCount(['penawarans as jumlah_penawaran']);

        if ($search = $request->query('search')) {
            $q->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('nama_perusahaan', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('as_list')) {
            return response()->json($q->select(['id_customer', 'nama_perusahaan'])->orderBy('nama_perusahaan')->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return response()->json($q->paginate($perPage));
    }

    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();

        $data['id_user']      = $request->user()->id;
        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;
        $data['nama_perusahaan'] = $this->normalizeName($data['nama_perusahaan'] ?? null);

        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::create($data);
            $this->seedRelatedRecords($customer);
            return $customer;
        });

        return response()->json($customer, 201);
    }

    public function show(Request $request, Customer $customer)
    {
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($customer->load(['user', 'provinsi', 'kabupaten']));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $data['id_user']         = $request->user()->id;
        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;
        $data['nama_perusahaan'] = $this->normalizeName($data['nama_perusahaan'] ?? null);

        $customer->update($data);

        return response()->json($customer);
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($customer->penawarans()->exists()) {
            return response()->json([
                'message' => 'Customer tidak dapat dihapus karena memiliki data penawaran terkait.',
            ], 422);
        }

        $customer->delete();

        return response()->json(null, 204);
    }

    private function seedRelatedRecords(Customer $customer): void
    {
        $id = $customer->id_customer;

        CustomerContact::firstOrCreate(['id_customer' => $id], [
            'pic_decision_telp'   => '',
            'pic_decision_mobile' => '',
            'pic_ordering_telp'   => '',
            'pic_ordering_mobile' => '',
            'pic_billing_telp'    => '',
            'pic_billing_mobile'  => '',
            'pic_invoice_telp'    => '',
            'pic_invoice_mobile'  => '',
        ]);

        CustomerLogistik::firstOrCreate(['id_customer' => $id], [
            'logistik_area'    => '',
            'logistik_bisnis'  => '',
            'logistik_env'     => 0,
            'logistik_storage' => 0,
            'logistik_hour'    => 0,
            'logistik_volume'  => 0,
            'logistik_quality' => 0,
            'logistik_truck'   => 0,
            'desc_stor_fac'    => '',
            'desc_condition'   => '',
        ]);

        CustomerPayment::firstOrCreate(['id_customer' => $id], [
            'telp_billing'     => '',
            'fax_billing'      => '',
            'payment_schedule' => 0,
            'payment_method'   => 0,
            'invoice'          => 0,
            'ket_extra'        => '',
        ]);

        CustomerAdminArnya::firstOrCreate(['id_customer' => $id], [
            'not_yet'     => 0,
            'ov_up_07'    => 0,
            'ov_under_30' => 0,
            'ov_under_60' => 0,
            'ov_under_90' => 0,
            'ov_up_90'    => 0,
        ]);
    }

    private function normalizeName(?string $name): ?string
    {
        if (empty($name)) {
            return $name;
        }

        return mb_strtoupper(trim($name), 'UTF-8');
    }
}
