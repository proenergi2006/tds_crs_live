<?php

namespace App\Http\Controllers\Customer;

use App\Enums\DocumentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerIndexResource;
use App\Models\Customer;
use App\Models\CustomerAdminArnya;
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

        $base = Customer::query();

        if ($user->cant('customer.viewAny')) {
            $base->where('id_user', $user->id);
        }

        if ($search = $request->query('search')) {
            $search = strtolower($search);

            $base->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->boolean('as_list')) {
            $list = (clone $base)
                ->with(['user', 'province', 'regency', 'district', 'village', 'cabang'])
                ->withExists(['lcr as has_lcr'])
                ->withCount(['penawarans as quotation_count'])
                ->get();

            return CustomerIndexResource::collection($list);
        }

        $tabCounts = [];

        foreach (['all', 'verified', 'unverified'] as $tab) {
            $tabQuery = clone $base;
            $this->applyStatusFilter($tabQuery, $tab);
            $tabCounts[$tab] = $tabQuery->count();
        }

        $q = (clone $base)
            ->with(['user', 'province', 'regency', 'district', 'village', 'cabang', 'latestVerification.latestDocumentApproval'])
            ->withExists(['lcr as has_lcr'])
            ->withCount(['penawarans as quotation_count'])
            ->orderBy('company_name');

        $this->applyStatusFilter($q, $request->query('status', 'all'));

        $paginator = $q->paginate($request->integer('per_page', 10));

        $paginator->getCollection()->each(
            fn(Customer $customer) => $customer->verification_badge = $this->resolveVerificationBadge($customer)
        );

        return response()->json([
            'data' => CustomerIndexResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
            'tab_counts' => $tabCounts,
        ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'verified') {
            $query->whereHas('latestVerification', fn($vq) => $vq->whereHas('latestDocumentApproval', fn($aq) => $aq->where('status', DocumentApprovalStatus::Approved)));
        } elseif ($status === 'unverified') {
            $query->where(function ($outer) {
                $outer->whereDoesntHave('latestVerification')
                    ->orWhereHas('latestVerification', fn($vq) => $vq->whereDoesntHave('latestDocumentApproval')
                        ->orWhereHas('latestDocumentApproval', fn($aq) => $aq->where('status', '!=', DocumentApprovalStatus::Approved)));
            });
        }
    }

    private function resolveVerificationBadge(Customer $customer): string
    {
        $latest = $customer->latestVerification;

        if (!$latest || !$customer->is_link_generated) {
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
        $data['created_at']   = now();
        $data['created_by']   = $request->user()->name;
        $data['company_name'] = $this->normalizeName($data['company_name'] ?? null);

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

        $customer->load([
            'user',
            'provinsi',
            'kabupaten',
            'province',
            'regency',
            'district',
            'village',
            'latestVerification.latestDocumentApproval.steps',
            'addresses.province',
            'addresses.regency',
            'addresses.district',
            'addresses.village',
            'contacts',
            'payment',
            'logistik',
            'lcr',
            'creditSubmissions',
        ]);
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

        $data['updated_at']   = now();
        $data['updated_by']   = $request->user()->name;
        $data['company_name'] = $this->normalizeName($data['company_name'] ?? null);

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

    public function checkCompanyName(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string',
            'exclude_id'   => 'nullable|integer|exists:customers,id_customer',
        ]);

        $normalized = $this->normalizeForComparison($data['company_name']);

        $query = Customer::whereNull('deleted_at')
            ->whereRaw("UPPER(REGEXP_REPLACE(TRIM(company_name), '\s+', ' ', 'g')) = ?", [$normalized])
            ->with('user');

        if (!empty($data['exclude_id'])) {
            $query->where('id_customer', '!=', $data['exclude_id']);
        }

        $matches = $query->get();

        return response()->json([
            'available' => $matches->isEmpty(),
            'matches'   => $matches->map(fn(Customer $c) => [
                'id_customer'  => $c->id_customer,
                'company_name' => $c->company_name,
                'marketing'    => [
                    'id'   => $c->user?->id,
                    'name' => $c->user?->name,
                ],
            ]),
        ]);
    }

    public function generateOnboardingLink(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $existing = CustomerVerification::where('id_customer', $customer->id_customer)
            ->where('is_active', true)
            ->latest('id_verification')
            ->first();

        if ($existing) {
            $isExpired = $existing->expired_at !== null && $existing->expired_at->lte(now());
            $isRejected = $existing->latestDocumentApproval?->status === DocumentApprovalStatus::Rejected;

            if (!$isExpired && !$isRejected) {
                $link = rtrim(config('app.frontend_url', config('app.url')), '/')
                    . '/customer-onboarding/' . $existing->verification_token;

                $customer->update(['is_link_generated' => true]);

                return response()->json([
                    'already_exists' => true,
                    'verification'   => $existing,
                    'link'           => $link,
                ]);
            }

            $existing->update(['is_active' => false]);
        }

        do {
            $token = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(17));
        } while (CustomerVerification::where('verification_token', $token)->exists());

        $cv = CustomerVerification::create([
            'id_customer'        => $customer->id_customer,
            'verification_token' => $token,
            'is_submitted'       => false,
            'is_forwarded'       => false,
            'is_active'          => true,
            'expired_at'         => now()->addDays(7),

            'legal_data'    => '',
            'legal_summary' => '',
            'legal_pic'     => '',

            'finance_data'    => '',
            'finance_summary' => '',
            'finance_pic'     => '',

            'logistics_data'    => '',
            'logistics_summary' => '',
            'logistics_pic'     => '',
        ]);

        $link = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/customer-onboarding/' . $token;

        $customer->update(['is_link_generated' => true]);

        return response()->json([
            'already_exists' => false,
            'verification'   => $cv,
            'link'           => $link,
        ], 201);
    }

    private function seedRelatedRecords(Customer $customer): void
    {
        $id = $customer->id_customer;

        // customer_contacts sekarang multi-row (0..N per customer, lihat
        // Customer::contacts()) -- tidak ada lagi header row untuk di-seed
        // seperti tabel 1:1 di bawah ini.

        // Semua kolom customer_logistik nullable sejak rebuild rev. 3 --
        // baris kosong cukup dibuat dengan id_customer saja.
        CustomerLogistik::firstOrCreate(['id_customer' => $id]);

        CustomerPayment::firstOrCreate(['id_customer' => $id], [
            'payment_schedule' => null,
            'payment_method'   => null,
            'invoice'          => false,
            'extra_notes'      => '',
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

    private function normalizeForComparison(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);

        return mb_strtoupper($name, 'UTF-8');
    }
}
