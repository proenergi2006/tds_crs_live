<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\CloseCustomerKycAction;
use App\Actions\Customer\GenerateCustomerDataDocumentAction;
use App\Actions\Customer\GenerateCustomerKycDocumentAction;
use App\Enums\CustomerKycStatus;
use App\Enums\CustomerReviewQuestionCode;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalTemplate;
use App\Models\Customer;
use App\Models\CustomerReview;
use App\Models\CustomerVerification;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomerVerificationController extends Controller
{
    private const APPROVAL_TEMPLATE_CODE = 'customer_verification';

    private const ROLE_ADMIN_FINANCE = 9;
    private const ROLE_BM            = 8;

    private function createDocumentApprovalCycle(CustomerVerification $cv): void
    {
        $template = $this->activeApprovalTemplate();

        if (!$template || $template->steps->count() < 2) {
            Log::error('Approval template customer_verification tidak ditemukan/tidak lengkap saat forward verification.', [
                'id_verification' => $cv->id_verification,
                'id_customer'     => $cv->id_customer,
                'template_found'  => (bool) $template,
                'steps_count'     => $template?->steps->count(),
            ]);
            throw new \RuntimeException('Approval template customer_verification belum ter-setup dengan benar.');
        }

        $approval = $cv->documentApprovals()->create([
            'id_template'        => $template->id_template,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => $template->steps->min('step_order'),
            'started_at'         => now(),
        ]);

        foreach ($template->steps as $step) {
            DocumentApprovalStep::create([
                'id_approval'      => $approval->id_approval,
                'id_template_step' => $step->id_step,
                'step_order'       => $step->step_order,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }
    }

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

    private function advanceApprovalStep(
        CustomerVerification $cv,
        int $stepOrder,
        DocumentApprovalStepStatus $status,
        ?string $note
    ): ?DocumentApproval {
        $approval = $cv->documentApprovals()
            ->with('template.steps')
            ->where('status', DocumentApprovalStatus::InProgress)
            ->latest('id_approval')
            ->first();

        if (!$approval) {
            Log::warning('Tidak ada document_approvals berstatus in_progress untuk verification ini saat mencoba advance step approval.', [
                'id_verification' => $cv->id_verification,
                'step_order'      => $stepOrder,
                'target_status'   => $status->value,
            ]);
            return null;
        }

        $step = $approval->steps()->where('step_order', $stepOrder)->first();

        if (!$step) {
            Log::warning('document_approval_steps untuk step_order ini tidak ditemukan pada document_approvals in_progress.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
            ]);
            return null;
        }

        $step->update([
            'status'        => $status,
            'actor_id'      => auth()->id(),
            'acted_at'      => now(),
            'decision_note' => $note,
        ]);

        if ($status === DocumentApprovalStepStatus::Rejected) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Rejected,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $templateSteps = $approval->template?->steps ?? collect();

        if ($templateSteps->isEmpty()) {
            Log::warning('Template/steps tidak ditemukan untuk document_approvals ini saat menentukan step terakhir — cycle ditutup sebagai approved untuk mencegah macet.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
            ]);

            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $maxStepOrder = $templateSteps->max('step_order');

        if ($stepOrder === $maxStepOrder) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $nextStepOrder = $templateSteps->pluck('step_order')
            ->filter(fn($order) => $order > $stepOrder)
            ->sort()
            ->first();

        if ($nextStepOrder === null) {
            Log::warning('Step ini bukan step_order maksimum tapi step berikutnya tidak ditemukan (data template_step tidak konsisten) — cycle ditutup sebagai approved untuk mencegah macet.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
                'max_step_order'  => $maxStepOrder,
            ]);

            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $approval->update([
            'current_step_order' => $nextStepOrder,
        ]);

        return $approval;
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

        $verification = CustomerVerification::create($data);

        Customer::where('id_customer', $data['id_customer'])
            ->update(['is_link_generated' => 1]);

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

        $scopedQuery = fn () => CustomerVerification::query()
            ->when(!$isAdminFinance, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('id_user', $user->id)));

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
            ->with(['customer' => fn ($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax', 'email')->withHeadOfficeAddressLine()]);

        if ($tab === 'draft') {
            $baseQuery->where('kyc_status', CustomerKycStatus::Draft);
        } else {
            $baseQuery->whereIn('kyc_status', [CustomerKycStatus::Forwarded, CustomerKycStatus::Closed]);
        }

        if (!$isAdminFinance) {
            $baseQuery->whereHas('customer', fn ($c) => $c->where('id_user', $user->id));
        }

        $rows = $baseQuery
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                        ->orWhereHas('headOfficeAddress', fn ($a) => $a->where('address_line', 'like', "%{$q}%"))
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
            'customer' => fn ($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax', 'email')->withHeadOfficeAddressLine(),
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

    // Gak nulis is_forwarded di sini, itu tanggung jawabnya forward().
    public function getReview(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = ($user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id)
            || $user->can('customer.viewAny');

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review = CustomerReview::where('id_verification', $id)->first();

        if (!$review) {
            $reviewAnswers = collect(CustomerReviewQuestionCode::cases())
                ->map(fn (CustomerReviewQuestionCode $code) => [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ])
                ->sortBy('order')
                ->values()
                ->all();

            return response()->json([
                'reviewed_at'        => null,
                'review_answers'     => $reviewAnswers,
                'review_attachments' => [],
            ]);
        }

        $reviewAnswers = collect($review->review_answers)
            ->map(function (array $item) {
                $code = CustomerReviewQuestionCode::tryFrom($item['question_code'] ?? '');

                if (!$code) {
                    return null;
                }

                return [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => $item['answer'] ?? null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values()
            ->all();

        return response()->json([
            'reviewed_at'        => $review->reviewed_at,
            'review_answers'     => $reviewAnswers,
            'review_attachments' => $review->review_attachments,
        ]);
    }

    public function saveReview(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = ($user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id)
            || $user->can('customer.viewAny');

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Draft) {
            return response()->json(['message' => 'KYC sudah diforward, Sales Review tidak bisa diubah lagi.'], 409);
        }

        $data = $request->validate([
            'review_answers'                  => 'required|array',
            'review_answers.*.question_code'  => ['required', new Enum(CustomerReviewQuestionCode::class)],
            'review_answers.*.answer'         => 'nullable|string',
        ]);

        $reviewAnswers = collect($data['review_answers'])
            ->map(function (array $item) {
                $code = CustomerReviewQuestionCode::from($item['question_code']);

                return [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => $item['answer'] ?? null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();

        $review = CustomerReview::updateOrCreate(
            ['id_verification' => $id],
            [
                'review_answers' => $reviewAnswers,
                'reviewed_at'    => now(),
            ]
        );

        return response()->json([
            'review_answers' => $review->review_answers,
            'reviewed_at'    => $review->reviewed_at,
        ]);
    }

    public function uploadReviewAttachment(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = ($user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id)
            || $user->can('customer.viewAny');

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Draft) {
            return response()->json(['message' => 'KYC sudah diforward, Sales Review tidak bisa diubah lagi.'], 409);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
        ]);

        $review = CustomerReview::firstOrCreate(['id_verification' => $id]);

        $file = $request->file('file');
        $path = $file->store("customer_verifications/{$id}/review", 'public');
        $originalName = $file->getClientOriginalName();

        $attachments = $review->review_attachments ?? [];
        $attachments[] = [
            'path'          => $path,
            'url'           => Storage::disk('public')->url($path),
            'original_name' => $originalName,
        ];

        $review->update(['review_attachments' => $attachments]);

        return response()->json([
            'index'         => array_key_last($attachments),
            'path'          => $path,
            'url'           => Storage::disk('public')->url($path),
            'original_name' => $originalName,
        ]);
    }

    public function deleteReviewAttachment(int $id, int $no): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = ($user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id)
            || $user->can('customer.viewAny');

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Draft) {
            return response()->json(['message' => 'KYC sudah diforward, Sales Review tidak bisa diubah lagi.'], 409);
        }

        $review = CustomerReview::where('id_verification', $id)->firstOrFail();

        $attachments = $review->review_attachments ?? [];

        if (!isset($attachments[$no])) {
            return response()->json(['message' => 'Attachment tidak ditemukan.'], 404);
        }

        Storage::disk('public')->delete($attachments[$no]['path']);

        array_splice($attachments, $no, 1);

        $review->update(['review_attachments' => array_values($attachments)]);

        return response()->json(['ok' => true]);
    }

    // forward butuh Tab 2 (review answers lengkap) & Tab 4 (credit_limit_request) siap -- Tab 1 udah ditegakkan pas onboarding.
    public function forward(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = $user->can('customer.manage') && $this->verificationOwnerId($cv) === $user->id;

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cv->kyc_status !== CustomerKycStatus::Draft) {
            return response()->json(['message' => 'KYC sudah pernah di-forward.'], 409);
        }

        $incompleteTabs = [];

        $review         = CustomerReview::where('id_verification', $id)->first();
        $answeredCodes  = collect($review ? ($review->review_answers ?? []) : [])
            ->filter(fn (array $item) => isset($item['answer']) && $item['answer'] !== '')
            ->pluck('question_code');

        $reviewComplete = $review
            && collect(CustomerReviewQuestionCode::cases())
                ->every(fn (CustomerReviewQuestionCode $code) => $answeredCodes->contains($code->value));

        if (!$reviewComplete) {
            $incompleteTabs[] = 'review';
        }

        $submission = $cv->customer->latestCreditSubmission;

        if (!$submission || $submission->credit_limit_request === null) {
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

    // Terpisah dari document() -- Penawaran Lookup sengaja gak diikutkan. Gating sama kayak document().
    public function dataCustomerDocument(Request $request, int $id, GenerateCustomerDataDocumentAction $action)
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        if (!in_array($cv->kyc_status, [CustomerKycStatus::Forwarded, CustomerKycStatus::Closed], true)) {
            return response()->json(['message' => 'Dokumen KYC hanya bisa dicetak setelah di-forward.'], 409);
        }

        $data = $action->execute($cv->customer);

        // Base64 data URI, bukan URL -- dompdf tidak resolve URL eksternal/relative dengan reliable.
        $leftPath = public_path('images/logo-new.png');
        $rightPath = public_path('images/logo-crs.png');
        $data['logoLeft'] = file_exists($leftPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($leftPath)) : null;
        $data['logoRight'] = file_exists($rightPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($rightPath)) : null;

        $pdf = \PDF::loadView('customer.data-customer-document', $data)->setPaper('A4', 'portrait');

        $safeName = str_replace(['/', '\\'], '-', (string) $data['customer']->company_name);

        return $pdf->stream("Data-Customer-{$safeName}-{$cv->id_verification}.pdf");
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
            ->with(['customer' => fn ($c) => $c->select('id_customer', 'customer_code', 'company_name', 'phone', 'fax')->withHeadOfficeAddressLine()])
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                        ->orWhereHas('headOfficeAddress', fn ($a) => $a->where('address_line', 'like', "%{$q}%"))
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
                        ->orWhereHas('headOfficeAddress', fn ($a) => $a->where('address_line', 'ilike', "%{$q}%"))
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
