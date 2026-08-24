<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CloseCustomerKycAction;
use App\Actions\Customer\EvaluateCustomerTabCompletenessAction;
use App\Actions\Customer\GenerateCustomerDataDocumentAction;
use App\Actions\Customer\GenerateCustomerKycDocumentAction;
use App\Enums\CustomerKycStatus;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalTemplate;
use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerVerificationController extends Controller
{
    private const APPROVAL_TEMPLATE_CODE = 'customer_verification';

    private const ROLE_ADMIN_FINANCE = 9;
    private const ROLE_BM            = 8;

    private function activeApprovalTemplate(): ?ApprovalTemplate
    {
        return ApprovalTemplate::with('steps')
            ->where('code', self::APPROVAL_TEMPLATE_CODE)
            ->first();
    }

    private function resolveStepOrderForRole(ApprovalTemplate $template, int $idRole): ?int
    {
        return $template->steps->firstWhere('id_role', $idRole)?->step_order;
    }

    private function pendingStepQuery(int $idRole)
    {
        $template  = $this->activeApprovalTemplate();
        $stepOrder = $template ? $this->resolveStepOrderForRole($template, $idRole) : null;

        if ($stepOrder === null) {
            Log::warning('Tidak menemukan step_order untuk role ini di template customer_verification aktif saat membangun antrean pending.', [
                'id_role' => $idRole,
            ]);

            return CustomerVerification::query()->whereNull('id_verification');
        }

        return CustomerVerification::query()
            ->whereHas('documentApprovals', function ($approvalQuery) use ($stepOrder, $idRole) {
                $approvalQuery->where('status', DocumentApprovalStatus::InProgress)
                    ->where('current_step_order', $stepOrder)
                    ->whereHas('steps', function ($stepQuery) use ($stepOrder, $idRole) {
                        $stepQuery->where('step_order', $stepOrder)
                            ->where('status', DocumentApprovalStepStatus::Pending)
                            ->whereHas('templateStep', function ($templateStepQuery) use ($idRole) {
                                $templateStepQuery->where('id_role', $idRole);
                            });
                    });
            });
    }

    private function marketingQueueQuery()
    {
        return CustomerVerification::query()
            ->where('is_submitted', 1)
            ->where(function ($w) {
                $w->whereDoesntHave('documentApprovals')
                    ->orWhereHas('latestDocumentApproval', function ($approvalQuery) {
                        $approvalQuery->where('status', DocumentApprovalStatus::Rejected);
                    });
            });
    }

    private function verificationOwnerId(CustomerVerification $customerVerification): ?int
    {
        $ownerId = Customer::where('id_customer', $customerVerification->id_customer)->value('id_user');

        return $ownerId !== null ? (int) $ownerId : null;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('customer.viewAny') && $user->cant('customer.viewOwn')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) $request->query('per_page', 10);
        $search  = trim((string) $request->query('search', ''));

        // alias ke key lama (kode_pelanggan/nama_perusahaan) masih dibaca Index.vue; company_address diambil lewat subselect head_office
        $q = CustomerVerification::query()
            ->with(['customer' => function ($c) {
                $c->select(
                    'id_customer',
                    'id_user',
                    DB::raw('customer_code as kode_pelanggan'),
                    DB::raw('company_name as nama_perusahaan'),
                    'email',
                    'phone',
                    'fax'
                )->withHeadOfficeAddressLine();
            }]);

        if ($user->cant('customer.viewAny')) {
            $q->whereHas('customer', function ($c) use ($user) {
                $c->where('id_user', $user->id);
            });
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('verification_token', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('company_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $q->orderByDesc('id_verification')->paginate($perPage);

        return response()->json([
            'data'               => $rows->items(),
            'current_page'       => $rows->currentPage(),
            'last_page'          => $rows->lastPage(),
            'total'              => $rows->total(),
            'total_verification' => (int) $rows->total(),
        ]);
    }

    public function show(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($customerVerification) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $customerVerification->loadMissing('customer:id_customer,company_name');
    }

    public function store(Request $request)
    {
        if ($request->user()->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'verification_token' => ['nullable', 'string', 'size:17', 'unique:customer_verifications,verification_token'],

            'is_submitted' => ['nullable', 'integer', 'in:0,1'],
            'is_forwarded' => ['nullable', 'integer', 'in:0,1'],
            'is_active'    => ['nullable', 'integer', 'in:0,1'],

            'finance_data'    => ['nullable', 'string'],
            'finance_summary' => ['nullable', 'string'],
            'finance_result'  => ['nullable', 'integer'],
            'finance_processed_at' => ['nullable', 'date'],
            'finance_pic'     => ['nullable', 'string', 'max:50'],

            'logistics_data'    => ['nullable', 'string'],
            'logistics_summary' => ['nullable', 'string'],
            'logistics_result'  => ['nullable', 'integer'],
            'logistics_processed_at' => ['nullable', 'date'],
            'logistics_pic'     => ['nullable', 'string', 'max:50'],

            'data_type'        => ['nullable', 'integer'],
            'finance_data_kyc' => ['nullable', 'string'],
        ]);

        if (empty($data['verification_token'])) {
            $data['verification_token'] = strtoupper(Str::random(17));
        }

        // default value nya gak di-set di db, jadi diisi manual di sini
        foreach (['finance_data', 'finance_summary', 'finance_pic', 'logistics_data', 'logistics_summary', 'logistics_pic'] as $key) {
            $data[$key] ??= '';
        }

        $verification = CustomerVerification::create($data);

        return response()->json(
            $verification->loadMissing('customer:id_customer,company_name'),
            201
        );
    }

    public function update(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'id_customer'        => ['sometimes', 'exists:customers,id_customer'],
            'verification_token' => [
                'sometimes',
                'string',
                'size:17',
                Rule::unique('customer_verifications', 'verification_token')
                    ->ignore($customerVerification->id_verification, 'id_verification'),
            ],

            'is_submitted' => ['sometimes', 'integer', 'in:0,1'],
            'is_forwarded' => ['sometimes', 'integer', 'in:0,1'],
            'is_active'    => ['sometimes', 'integer', 'in:0,1'],

            'finance_data'    => ['sometimes', 'nullable', 'string'],
            'finance_summary' => ['sometimes', 'nullable', 'string'],
            'finance_result'  => ['sometimes', 'nullable', 'integer'],
            'finance_processed_at' => ['sometimes', 'nullable', 'date'],
            'finance_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'logistics_data'    => ['sometimes', 'nullable', 'string'],
            'logistics_summary' => ['sometimes', 'nullable', 'string'],
            'logistics_result'  => ['sometimes', 'nullable', 'integer'],
            'logistics_processed_at' => ['sometimes', 'nullable', 'date'],
            'logistics_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'data_type'        => ['sometimes', 'nullable', 'integer'],
            'finance_data_kyc' => ['sometimes', 'nullable', 'string'],
        ]);

        $customerVerification->update($data);

        return $customerVerification->fresh()->loadMissing('customer:id_customer,company_name');
    }

    public function destroy(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customerVerification->delete();
        return response()->noContent();
    }

    // 1 endpoint 2 mode dari kyc_status -- Marketing cuma liat draft sendiri, Admin Finance liat forwarded+closed lintas-marketing.
    public function reviewStats(Request $r)
    {
        $user = $r->user();

        if ($user->cant('verification.customer') && $user->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isAdminFinance = $user->can('verification.customer');

        $scopedQuery = fn() => CustomerVerification::query()
            ->when(!$isAdminFinance, fn($q) => $q->whereHas('customer', fn($c) => $c->where('id_user', $user->id)));

        return response()->json([
            'draft'     => $scopedQuery()->where('kyc_status', CustomerKycStatus::Draft)->count(),
            'forwarded' => $scopedQuery()->where('kyc_status', CustomerKycStatus::Forwarded)->count(),
            'closed'    => $scopedQuery()->where('kyc_status', CustomerKycStatus::Closed)->count(),
        ]);
    }

    public function reviewIndex(Request $r)
    {
        $user = $r->user();

        if ($user->cant('verification.customer') && $user->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isAdminFinance = $user->can('verification.customer');

        $per = (int) $r->query('per_page', 25);
        $q   = trim((string) $r->query('q', ''));
        $tab = $r->query('tab', $r->query('status', $isAdminFinance ? 'forwarded' : 'draft'));

        $baseQuery = CustomerVerification::query()
            ->with(['customer' => fn($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax', 'email')->withHeadOfficeAddressLine()]);

        if ($tab === 'draft') {
            $baseQuery->where('kyc_status', CustomerKycStatus::Draft);
        } else {
            $baseQuery->whereIn('kyc_status', [CustomerKycStatus::Forwarded, CustomerKycStatus::Closed]);
        }

        if (!$isAdminFinance) {
            $baseQuery->whereHas('customer', fn($c) => $c->where('id_user', $user->id));
        }

        $rows = $baseQuery
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                        ->orWhereHas('headOfficeAddress', fn($a) => $a->where('address_line', 'like', "%{$q}%"))
                        ->orWhere('customer_code', 'like', "%{$q}%");
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
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $row = \App\Models\CustomerVerification::findOrFail($id);
        $row->update(['is_forwarded' => 1]);
        return response()->json(['ok' => true]);
    }

    // satu-satunya cara resolve id_customer dari id_verification -- itu doang route param yang ada di halaman verifikasi Admin Finance.
    public function reviewShow(int $id)
    {
        $user = auth()->user();

        $cv = CustomerVerification::with([
            'customer' => fn($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax', 'email')->withHeadOfficeAddressLine(),
        ])->findOrFail($id);

        $allowed = $user->can('verification.customer')
            || $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'id_verification' => $cv->id_verification,
            'id_customer'     => $cv->id_customer,
            'kyc_status'      => $cv->kyc_status?->value,
            'is_forwarded'    => (bool) $cv->is_forwarded,
            'customer'        => $cv->customer,
        ]);
    }

    public function approvalTimeline(int $id)
    {
        $user = auth()->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cycles = $cv->documentApprovals()
            ->with([
                'steps' => function ($q) {
                    $q->orderBy('step_order');
                },
                'steps.templateStep:id_step,step_name,step_order,id_role',
                'steps.actor:id,name',
            ])
            ->orderByDesc('id_approval')
            ->get();

        return response()->json([
            'data' => $cycles->map(function (DocumentApproval $cycle) {
                return [
                    'id_approval'        => $cycle->id_approval,
                    'status'             => $cycle->status,
                    'current_step_order' => $cycle->current_step_order,
                    'started_at'         => $cycle->started_at,
                    'completed_at'       => $cycle->completed_at,
                    'steps'              => $cycle->steps->map(function (DocumentApprovalStep $step) {
                        return [
                            'step_order'    => $step->step_order,
                            'step_name'     => $step->templateStep->step_name ?? null,
                            'status'        => $step->status,
                            'actor_id'      => $step->actor_id,
                            'actor_name'    => $step->actor->name ?? null,
                            'acted_at'      => $step->acted_at,
                            'decision_note' => $step->decision_note,
                        ];
                    }),
                ];
            }),
        ]);
    }

    // forward butuh Tab 2 (review answers lengkap) & Tab 4 (credit_limit_request + top_request) siap -- Tab 1 udah ditegakkan pas onboarding.
    public function forward(Request $request, int $id, EvaluateCustomerTabCompletenessAction $evaluateTabCompleteness): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::with([
            'customer.addresses',
            'customer.payment',
            'customer.contacts',
            'customer.documents.documentType',
        ])->findOrFail($id);

        $allowed = $user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id;

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Draft) {
            return response()->json(['message' => 'KYC sudah pernah di-forward.'], 409);
        }

        $completeness = $evaluateTabCompleteness->execute($cv->customer);

        $incompleteTabs = [];

        if (!$completeness['review']) {
            $incompleteTabs[] = 'review';
        }

        if (!$completeness['credit']) {
            $incompleteTabs[] = 'credit';
        }

        if (!empty($incompleteTabs)) {
            return response()->json([
                'message'         => 'Tidak bisa forward, ada tab yang belum lengkap.',
                'incomplete_tabs' => $incompleteTabs,
            ], 422);
        }

        $cv->update([
            'is_forwarded' => true,
            'kyc_status'   => CustomerKycStatus::Forwarded,
        ]);

        return response()->json([
            'kyc_status'   => CustomerKycStatus::Forwarded->value,
            'is_forwarded' => true,
        ]);
    }

    // nutup KYC final & gak bisa di-undo -- role 9 dicek eksplisit karena BM (role 8) punya permission sama tapi gak boleh nutup.
    public function close(Request $request, int $id, CloseCustomerKycAction $action): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'credit_limit_approval' => 'required|numeric|min:0',
            'top_approval'          => 'required|numeric|min:0',
            'financial_review'      => 'nullable|string',
        ]);

        $cv = CustomerVerification::findOrFail($id);

        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ((int) $request->user()->id_role !== self::ROLE_ADMIN_FINANCE) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Forwarded) {
            return response()->json(['message' => 'KYC belum di-forward atau sudah ditutup.'], 409);
        }

        $submission = $cv->customer->latestCreditSubmission;

        if (!$submission) {
            return response()->json(['message' => 'Belum ada pengajuan credit untuk customer ini.'], 422);
        }

        $submission = $action->execute($cv, $submission, (int) $data['credit_limit_approval'], (int) $data['top_approval'], $data['financial_review'] ?? null);

        return response()->json([
            'kyc_status'            => CustomerKycStatus::Closed->value,
            'credit_limit_approval' => $submission->credit_limit_approval,
            'top_approval'          => $submission->top_approval,
            'financial_review'      => $submission->financial_review,
        ]);
    }

    // gabungin Data Customer + Sales Review + LCR + Credit Application + Penawaran Lookup jadi 1 PDF buat rapat management -- cuma bisa diakses setelah forwarded/closed.
    public function document(Request $request, int $id, GenerateCustomerKycDocumentAction $action)
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        if (!in_array($cv->kyc_status, [CustomerKycStatus::Forwarded, CustomerKycStatus::Closed], true)) {
            return response()->json(['message' => 'Dokumen KYC hanya bisa dicetak setelah di-forward.'], 409);
        }

        $data = $action->execute($cv);

        $pdf = \PDF::loadView('customer.kyc-document', $data)->setPaper('A4', 'portrait');

        $safeName = str_replace(['/', '\\'], '-', (string) $data['customer']->company_name);

        return $pdf->stream("KYC-{$safeName}-{$cv->id_verification}.pdf");
    }

    // Dinonaktifkan sementara: template cetak Data Customer masih mengacu struktur kontak lama, akan didesain ulang.
    public function dataCustomerDocument(Request $request, int $id, GenerateCustomerDataDocumentAction $action)
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['message' => 'Fitur cetak Data Customer sedang dalam perbaikan, akan tersedia kembali.'], 503);
    }

    // antrean diambil dari document_approval_steps step 1 pending (Admin Finance, id_role=9), bukan dari is_active/is_reviewed/disposisi_result.
    public function reviewAdminIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $per = (int) $r->query('per_page', 25);
        $q   = trim((string) $r->query('q', ''));

        $rows = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)
            ->with(['customer' => fn($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax')->withHeadOfficeAddressLine()])
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                        ->orWhereHas('headOfficeAddress', fn($a) => $a->where('address_line', 'like', "%{$q}%"))
                        ->orWhere('customer_code', 'like', "%{$q}%");
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
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $queue = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)->count();

        return response()->json(['queue' => $queue]);
    }

    // antrean diambil dari document_approval_steps step 2/FINAL pending (BM, id_role=8), bukan dari is_reviewed/disposisi_result.
    public function reviewBmIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q       = trim((string) $r->query('q', ''));
        $perPage = (int) ($r->query('per_page', 25));

        $rows = $this->pendingStepQuery(self::ROLE_BM)
            ->with(['customer'])
            ->when($q, function ($qq) use ($q) {
                $qq->whereHas('customer', function ($c) use ($q) {
                    $c->where('company_name', 'ilike', "%{$q}%")
                        ->orWhereHas('headOfficeAddress', fn($a) => $a->where('address_line', 'ilike', "%{$q}%"))
                        ->orWhere('customer_code', 'ilike', "%{$q}%");
                });
            })
            ->orderByDesc('id_verification')
            ->paginate($perPage);

        return response()->json($rows);
    }

    public function reviewBmStats()
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $queue = $this->pendingStepQuery(self::ROLE_BM)->count();
        return response()->json(['queue' => $queue]);
    }
}
