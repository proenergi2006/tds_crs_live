<?php

namespace App\Http\Controllers\Customer;

use App\Enums\DocumentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAdminArnya;
use App\Models\CustomerContact;
use App\Models\CustomerLogistik;
use App\Models\CustomerPayment;
use App\Models\CustomerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('customer.viewAny') && $user->cant('customer.viewOwn')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q = Customer::query()
            ->with(['user', 'provinsi', 'kabupaten', 'province', 'regency', 'district', 'village', 'cabang'])
            ->withExists(['lcr as has_lcr'])
            ->withCount(['penawarans as jumlah_penawaran']);

        if ($user->cant('customer.viewAny')) {
            $q->where('id_user', $user->id);
        }

        if ($search = $request->query('search')) {
            $q->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('nama_perusahaan', 'like', "%{$search}%");
            });
        }

        $baseQuery = clone $q;

        $tabCounts = [
            'all'        => (clone $baseQuery)->count(),
            'verified'   => 0,
            'unverified' => 0,
        ];

        $tab = $request->query('tab', 'all');

        if ($request->boolean('as_list')) {
            return response()->json($q->select(['id_customer', 'nama_perusahaan'])->orderBy('nama_perusahaan')->get());
        }

        $q->with('latestVerification.latestDocumentApproval.steps');

        $perPage = min((int) $request->query('per_page', 10), 100);

        $paginated = $q->paginate($perPage)->through(function (Customer $customer) {
            $customer->verification_badge  = $this->resolveVerificationBadge($customer);
            $customer->latest_verification  = $this->formatLatestVerification($customer->latestVerification);
            return $customer;
        });

        $response = $paginated->toArray();
        $response['tab_counts'] = $tabCounts;

        return response()->json($response);
    }

    private function resolveVerificationBadge(Customer $customer): string
    {
        $latest = $customer->latestVerification;

        if (!$latest || (int) ($customer->is_generated_link ?? 0) === 0) {
            return 'belum_ada_link';
        }

        $latestCycle = $latest->latestDocumentApproval;

        if ($latestCycle?->status === DocumentApprovalStatus::Approved) {
            return 'verified';
        }

        $isExpired = $latest->expired_at !== null && $latest->expired_at->lte(now());

        if (!$latest->is_submitted && !$isExpired) {
            return 'menunggu_customer';
        }

        if (!$latest->is_submitted && $isExpired) {
            return 'link_kedaluwarsa';
        }

        if ($latest->is_submitted && !$latest->is_forwarded) {
            return 'perlu_direview';
        }

        if ($latestCycle?->status === DocumentApprovalStatus::Rejected) {
            return 'ditolak';
        }

        if ($latest->is_forwarded && $latestCycle?->status === DocumentApprovalStatus::InProgress) {
            return 'proses_internal';
        }

        return 'belum_ada_link';
    }

    private function formatLatestVerification(?CustomerVerification $verification): ?array
    {
        if (!$verification) {
            return null;
        }

        return [
            'id_verification' => $verification->id_verification,
            'is_submitted'    => (bool) $verification->is_submitted,
            'is_forwarded'    => (bool) $verification->is_forwarded,
            'is_active'       => (bool) $verification->is_active,
            'expired_at'      => optional($verification->expired_at)->toISOString(),
            'stage_label'     => $verification->stageLabel(),
        ];
    }

    public function store(StoreCustomerRequest $request)
    {
        if ($request->user()->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer->load(['user', 'provinsi', 'kabupaten', 'province', 'regency', 'district', 'village', 'latestVerification.latestDocumentApproval.steps']);
        $customer->latest_verification = $this->formatLatestVerification($customer->latestVerification);

        return response()->json($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;
        $data['nama_perusahaan'] = $this->normalizeName($data['nama_perusahaan'] ?? null);

        $customer->update($data);

        return response()->json($customer);
    }

    public function destroy(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
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
