<?php

namespace App\Http\Controllers\Customer;

use App\Enums\CustomerCreditSubmissionType;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalTemplate;
use App\Models\Customer;
use App\Models\CustomerCreditSubmission;
use App\Models\CustomerReview;
use App\Models\CustomerReviewAttachment;
use App\Models\CustomerVerification;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        $q = CustomerVerification::query()
            ->with([
                'customer:id_customer,id_user,kode_pelanggan,nama_perusahaan,alamat_perusahaan,email,telepon,fax'
            ]);

        if ($user->cant('customer.viewAny')) {
            $q->whereHas('customer', function ($c) use ($user) {
                $c->where('id_user', $user->id);
            });
        }

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

    public function show(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($customerVerification) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $customerVerification->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function getAdminEvaluation(Request $request, $id)
    {
        $cv = CustomerVerification::with('customer.latestCreditSubmission')->findOrFail($id);

        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer = $cv->customer;
        $topPayment = $customer->latestCreditSubmission->top_payment ?? null;

        return response()->json([
            'top_text'             => $topPayment ? $topPayment . ' Hari' : '-',
            'credit_limit_request' => $customer->latestCreditSubmission->credit_limit_request ?? '-',
            'financial_review'     => $cv->finance_summary ?? '-',
            'potential_volume'     => '-',
        ]);
    }


    public function store(Request $request)
    {
        if ($request->user()->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'token_verification' => ['nullable', 'string', 'size:17', 'unique:customer_verifications,token_verification'],

            'is_submitted' => ['nullable', 'integer', 'in:0,1'],
            'is_forwarded' => ['nullable', 'integer', 'in:0,1'],
            'is_active'    => ['nullable', 'integer', 'in:0,1'],

            'legal_data'     => ['nullable', 'string'],
            'legal_summary'  => ['nullable', 'string'],
            'legal_result'   => ['nullable', 'integer'],
            'legal_tgl_proses' => ['nullable', 'date'],
            'legal_pic'      => ['nullable', 'string', 'max:50'],

            'finance_data'    => ['nullable', 'string'],
            'finance_summary' => ['nullable', 'string'],
            'finance_result'  => ['nullable', 'integer'],
            'finance_tgl_proses' => ['nullable', 'date'],
            'finance_pic'     => ['nullable', 'string', 'max:50'],

            'logistik_data'    => ['nullable', 'string'],
            'logistik_summary' => ['nullable', 'string'],
            'logistik_result'  => ['nullable', 'integer'],
            'logistik_tgl_proses' => ['nullable', 'date'],
            'logistik_pic'     => ['nullable', 'string', 'max:50'],

            'jenis_datanya'    => ['nullable', 'integer'],
            'finance_data_kyc' => ['nullable', 'string'],
        ]);

        if (empty($data['token_verification'])) {
            $data['token_verification'] = strtoupper(Str::random(17));
        }

        $verification = CustomerVerification::create($data);

        Customer::where('id_customer', $data['id_customer'])
            ->update(['is_generated_link' => 1]);

        return response()->json(
            $verification->loadMissing('customer:id_customer,nama_perusahaan'),
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
            'token_verification' => [
                'sometimes',
                'string',
                'size:17',
                Rule::unique('customer_verifications', 'token_verification')
                    ->ignore($customerVerification->id_verification, 'id_verification'),
            ],

            'is_submitted' => ['sometimes', 'integer', 'in:0,1'],
            'is_forwarded' => ['sometimes', 'integer', 'in:0,1'],
            'is_active'    => ['sometimes', 'integer', 'in:0,1'],

            'legal_data'     => ['sometimes', 'nullable', 'string'],
            'legal_summary'  => ['sometimes', 'nullable', 'string'],
            'legal_result'   => ['sometimes', 'nullable', 'integer'],
            'legal_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'legal_pic'      => ['sometimes', 'nullable', 'string', 'max:50'],

            'finance_data'    => ['sometimes', 'nullable', 'string'],
            'finance_summary' => ['sometimes', 'nullable', 'string'],
            'finance_result'  => ['sometimes', 'nullable', 'integer'],
            'finance_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'finance_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'logistik_data'    => ['sometimes', 'nullable', 'string'],
            'logistik_summary' => ['sometimes', 'nullable', 'string'],
            'logistik_result'  => ['sometimes', 'nullable', 'integer'],
            'logistik_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'logistik_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'jenis_datanya'    => ['sometimes', 'nullable', 'integer'],
            'finance_data_kyc' => ['sometimes', 'nullable', 'string'],
        ]);

        $customerVerification->update($data);

        return $customerVerification->fresh()->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function upload(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,nib_file,other_file',
        ]);

        $path = $request->file('file')->store(
            "customer_verifications/{$customerVerification->id_verification}/{$request->field}",
            'public'
        );

        $legal = json_decode($customerVerification->legal_data ?? '{}', true) ?: [];
        $legal['files'][$request->field][] = $path;
        $customerVerification->update(['legal_data' => json_encode($legal)]);

        return response()->json(['path' => Storage::url($path)]);
    }

    public function uploadByToken(Request $request, string $token)
    {
        $cv = CustomerVerification::where('token_verification', $token)
            ->where('is_active', 1)->firstOrFail();

        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,nib_file,other_file',
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

    public function reviewStats()
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $unreviewed = $this->marketingQueueQuery()->count();

        $reviewed = CustomerVerification::query()
            ->where('is_submitted', 1)
            ->whereHas('latestDocumentApproval', function ($approvalQuery) {
                $approvalQuery->whereIn('status', [
                    DocumentApprovalStatus::InProgress,
                    DocumentApprovalStatus::Approved,
                ]);
            })
            ->count();

        return response()->json([
            'unreviewed' => $unreviewed,
            'reviewed'   => $reviewed,
        ]);
    }

    public function reviewIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $per    = (int) $r->query('per_page', 25);
        $q      = trim((string) $r->query('q', ''));
        $status = $r->query('tab', $r->query('status', 'unreviewed'));

        $baseQuery = $status === 'reviewed'
            ? CustomerVerification::query()->where('is_submitted', 1)->whereHas('latestDocumentApproval', function ($approvalQuery) {
                $approvalQuery->whereIn('status', [
                    DocumentApprovalStatus::InProgress,
                    DocumentApprovalStatus::Approved,
                ]);
            })
            : $this->marketingQueueQuery();

        $rows = $baseQuery
            ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
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


    public function setReviewed(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $row = \App\Models\CustomerVerification::findOrFail($id);
        $row->update(['is_forwarded' => 1]);
        return response()->json(['ok' => true]);
    }

    public function reviewShow(int $id)
    {
        $user = auth()->user();

        $cv = CustomerVerification::with([
            'customer:id_customer,nama_perusahaan,alamat_perusahaan,telepon,fax,email'
        ])->findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'customer'     => $cv->customer,
            'is_forwarded' => (int) $cv->is_forwarded,
            'stage_text'   => $cv->stageLabel(),
            'legal'        => json_decode($cv->legal_data    ?? '[]', true) ?: [],
            'finance'      => json_decode($cv->finance_data  ?? '[]', true) ?: [],
            'logistik'     => json_decode($cv->logistik_data ?? '[]', true) ?: [],
            'review_form'  => (function () use ($cv) {
                $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
                return $kyc['form'] ?? [];
            })(),
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

    public function evaluationShow(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv  = CustomerVerification::findOrFail($id);
        $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];

        return response()->json([
            'jenis_datanya' => (int) ($cv->jenis_datanya ?? 0),
            'evaluation'    => $kyc['evaluation'] ?? [],
        ]);
    }

    public function evaluationUploadFile(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        $user = auth()->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        $user = $r->user();

        $cv = CustomerVerification::select('id_verification', 'id_customer')->findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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

            'credit_limit_diajukan'    => 'nullable|string',
            'review_form'              => 'nullable|array',
            'reset_attachments'        => 'sometimes|boolean',
        ]);

        $rawCL = $data['credit_limit_diajukan']
            ?? data_get($data, 'review_form.detail.credit_limit_proposed')
            ?? ($data['review1'] ?? null);

        $creditLimit = null;
        if (!is_null($rawCL)) {
            $creditLimit = (int) preg_replace('/\D+/', '', (string) $rawCL);
        }

        $hasInProgressCycle = $cv->documentApprovals()
            ->where('status', DocumentApprovalStatus::InProgress)
            ->exists();

        if ($hasInProgressCycle) {
            return response()->json([
                'message' => 'Verifikasi ini masih memiliki siklus persetujuan yang sedang berjalan (Admin Finance/BM belum memutuskan) — tidak bisa forward ulang.',
            ], 422);
        }

        DB::transaction(function () use ($cv, $data, $creditLimit, $r) {

            $row = CustomerReview::updateOrCreate(
                ['id_verification' => $cv->id_verification],
                array_merge($data, [
                    'review_tanggal' => now(),
                    'review_pic'     => $data['review_pic'] ?? (auth()->user()->name ?? null),
                ])
            );

            CustomerVerification::where('id_verification', $cv->id_verification)
                ->update([
                    'is_forwarded' => 1,
                ]);

            $this->createDocumentApprovalCycle($cv);

            // credit_limit_request sudah pindah ke customer_credit_submissions
            // (aggregate customer-level, DBML-A/DBML-F) -- update submission
            // terbaru kalau ada, atau buat submission baru kalau customer ini
            // belum pernah punya satupun.
            $submission = CustomerCreditSubmission::where('id_customer', $cv->id_customer)
                ->latest('id_submission')
                ->first();

            if ($submission) {
                $submission->update(['credit_limit_request' => $creditLimit]);
            } else {
                CustomerCreditSubmission::create([
                    'id_customer'           => $cv->id_customer,
                    'submission_type'       => CustomerCreditSubmissionType::NewCustomer,
                    'credit_limit_request'  => $creditLimit,
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
            'message' => 'Review tersimpan & diforward ke Admin Finance.',
        ]);
    }

    public function uploadReviewAttachment(Request $r, int $id)
    {
        $user = $r->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        $user = auth()->user();

        $cv = CustomerVerification::findOrFail($id);

        $allowed = $user->can('verification.customer')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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

        return response()->json(['ok' => true]);
    }

    /**
     * (CA8, pivot 2026-07-10, dulu C6) Rewire: antrean Admin Finance sekarang
     * berdasarkan document_approval_steps step 1 (Admin Finance jadi step
     * pertama di model 2-step baru, dulu step 2) berstatus pending (role
     * id_role=9), bukan is_active/is_reviewed/disposisi_result.
     */
    public function reviewAdminIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $per = (int) $r->query('per_page', 25);
        $q   = trim((string) $r->query('q', ''));

        $rows = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)
            ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
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
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $queue = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)->count();

        return response()->json(['queue' => $queue]);
    }

    public function getEvaluation(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
        ]);
    }

    public function saveEvaluation(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Validasi sesuai payload FE (approval di dalam form)
        $payload = $r->validate([
            // (Prioritas C-Amend gap-fix, 2026-07-10) reject path Admin
            // Finance -- pola sama persis dengan bmVerify(): 'decision'
            // nullable APPROVE/REJECT (default APPROVE bila tidak dikirim),
            // 'notes' dipakai untuk decision_note reject supaya nama field
            // konsisten antara kedua endpoint step approval ini.
            'decision'                           => ['nullable', 'in:APPROVE,REJECT'],
            'notes'                              => ['nullable', 'string'],

            'form'                               => ['required', 'array'],
            'form.top_text'                      => ['nullable', 'string'],
            'form.potential_volume'              => ['nullable', 'string'],
            'form.jenis_data'                    => ['required', 'in:SEBELUM,SETELAH'],
            'form.financial_review'              => ['nullable', 'string'],

            'form.evaluation_numbers'            => ['nullable', 'array'],
            'form.evaluation_numbers.*'          => ['nullable', 'string'],

            'form.kyc_rows'                      => ['nullable', 'array'],
            'form.kyc_rows.*.label'              => ['nullable', 'string'],
            'form.kyc_rows.*.name'               => ['nullable', 'string'],
            'form.kyc_rows.*.path'               => ['nullable', 'string'],
            'form.kyc_rows.*.url'                => ['nullable', 'string'],

            'form.approval'                      => ['nullable', 'array'],
            'form.approval.approval_credit_limit' => ['nullable', 'string'],
            'form.approval.group_company'        => ['nullable', 'string'],

            'form.approval.docs'                 => ['nullable', 'array'],
            'form.approval.docs.customer_db'     => ['boolean'],
            'form.approval.docs.siup'            => ['boolean'],
            'form.approval.docs.notarial'        => ['boolean'],
            'form.approval.docs.lcr'             => ['boolean'],
            'form.approval.docs.npwp'            => ['boolean'],
            'form.approval.docs.finstat'         => ['boolean'],
            'form.approval.docs.top'             => ['boolean'],
            'form.approval.docs.customer_review' => ['boolean'],
            'form.approval.docs.others'          => ['boolean'],
            'form.approval.docs_others_text'     => ['nullable', 'string'],

            'form.approval.other_document'       => ['nullable', 'string'],
            'form.approval.logistik_summary'     => ['nullable', 'string'],
            'form.approval.logistik_result'      => ['nullable', 'string'],
            'form.approval.assessment_result'    => ['nullable', 'string'],
        ]);

        // default APPROVE, sama persis pola $data['decision'] ?? 'APPROVE' di bmVerify()
        $decision = $payload['decision'] ?? 'APPROVE';
        $notes    = $payload['notes'] ?? null;

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

        DB::transaction(function () use ($id, $form, $approval, $arrData, $incomingKyc, $summary, $creditLimit, $decision, $notes) {

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

            // (Prioritas C-Amend gap-fix, 2026-07-10) finance_result bergantung
            // ke $decision, bukan selalu dipaksa "lolos": APPROVE (default,
            // perilaku existing tidak berubah) -> finance_result=1, REJECT ->
            // finance_result=0 (konsisten dengan konvensi sm_result=0 untuk
            // reject di bmVerify() -- sibling _result column di controller
            // yang sama).
            //
            // Status/badge tampilan derive dari document_approvals lewat
            // CustomerVerification::stageLabel() (lihat
            // reviewShow()/CustomerController::formatLatestVerification()),
            // bukan kolom tersimpan di sini.
            $financeResult = $decision === 'REJECT' ? 0 : 1;

            // Update verification -- data evaluasi (KYC, ringkasan finansial,
            // dokumen, dst.) tetap ditulis apa adanya baik APPROVE maupun
            // REJECT: ini kerja/catatan Admin Finance yang sudah diinput,
            // tidak ada alasan bisnis untuk dibuang hanya karena hasil
            // keputusannya reject.
            $cv->update([
                'finance_data'        => json_encode($arrData, JSON_UNESCAPED_UNICODE),
                'jenis_datanya'       => $jenisDataInt,
                'finance_summary'     => $summary,
                'finance_result'      => $financeResult,
                'finance_tgl_proses'  => now(),
                'finance_pic'         => auth()->user()->name ?? null,
                'finance_data_kyc'    => json_encode($kycSaved, JSON_UNESCAPED_UNICODE),
            ]);

            // (CA5, pivot 2026-07-10, dulu C3; gap-fix 2026-07-10 menambah
            // reject) Approve -> step Admin Finance approved, current_step_order
            // maju ke step berikutnya (BM) di dalam advanceApprovalStep().
            // Reject -> step Admin Finance rejected, cycle document_approvals
            // ditutup rejected sepenuhnya di dalam advanceApprovalStep() (tidak
            // perlu kode eksplisit "kirim balik ke Marketing" -- queue
            // Marketing di CA7 sudah otomatis menangkap ini via
            // marketingQueueQuery()).
            //
            // (Prioritas H2, 2026-07-13) step_order literal (dulu
            // self::STEP_ADMIN_FINANCE) sekarang di-resolve dinamis: cari
            // step di template aktif yang id_role-nya cocok ROLE_ADMIN_FINANCE.
            // Kalau tidak ketemu (mis. admin ganti role step ini lewat CRUD
            // tanpa update kode), silent no-op + Log::warning -- perilaku
            // defensif yang sama seperti saat advanceApprovalStep() tidak
            // menemukan approval/step in_progress, TIDAK ada guard/error baru.
            $adminFinanceTemplate  = $this->activeApprovalTemplate();
            $adminFinanceStepOrder = $adminFinanceTemplate
                ? $this->resolveStepOrderForRole($adminFinanceTemplate, self::ROLE_ADMIN_FINANCE)
                : null;

            if ($adminFinanceStepOrder !== null) {
                $this->advanceApprovalStep(
                    $cv,
                    $adminFinanceStepOrder,
                    $decision === 'REJECT' ? DocumentApprovalStepStatus::Rejected : DocumentApprovalStepStatus::Approved,
                    $decision === 'REJECT' ? $notes : ($summary !== '' ? $summary : null)
                );
            } else {
                Log::warning('Tidak menemukan step_order untuk role Admin Finance di template customer_verification aktif saat saveEvaluation.', [
                    'id_verification' => $cv->id_verification,
                ]);
            }

            // Jika SETELAH komite, sinkron ke customers (opsional, tetap dipertahankan)
            if ($jenisDataInt === 2) {
                // approval.other_document cuma tersimpan di
                // customer_verifications.finance_data (JSON), tidak disalin ke customers.
                // TOP tersimpan lewat customer_credit_submissions (lihat
                // CustomerCreditSubmissionController), bukan lewat form evaluasi ini.
                // jenis_payment sudah drop (redundan dengan customer_payment.payment_method).
                // credit_limit_approval sudah pindah ke customer_credit_submissions
                // (aggregate customer-level, DBML-A/DBML-F) -- pola sama seperti
                // saveReview() untuk credit_limit_request.
                $submission = CustomerCreditSubmission::where('id_customer', $cv->id_customer)
                    ->latest('id_submission')
                    ->first();

                if ($submission) {
                    $submission->update(['credit_limit_approval' => $creditLimit]);
                } else {
                    CustomerCreditSubmission::create([
                        'id_customer'            => $cv->id_customer,
                        'submission_type'        => CustomerCreditSubmissionType::NewCustomer,
                        'credit_limit_approval'  => $creditLimit,
                    ]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    public function logistikShow(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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

    /**
     * (CA8, pivot 2026-07-10, dulu C6) Rewire: antrean BM sekarang berdasarkan
     * document_approval_steps step 2 (FINAL di model 2-step baru, dulu step 3)
     * berstatus pending (role id_role=8), bukan is_reviewed/disposisi_result.
     */
    public function reviewBmIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q       = trim((string) $r->query('q', ''));
        $perPage = (int) ($r->query('per_page', 25));

        $rows = $this->pendingStepQuery(self::ROLE_BM)
            ->with(['customer']) // pastikan relasi ada
            ->when($q, function ($qq) use ($q) {
                $qq->whereHas('customer', function ($c) use ($q) {
                    $c->where('nama_perusahaan', 'ilike', "%{$q}%")
                        ->orWhere('alamat_perusahaan', 'ilike', "%{$q}%")
                        ->orWhere('kode_pelanggan', 'ilike', "%{$q}%");
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

    /**
     * Simpan verifikasi BM (step 2, FINAL, di model 2-step baru — pivot
     * 2026-07-10):
     * - bm_notes: catatan BM
     * - bm_decision: APPROVE / REJECT (REVISE dihapus total, tidak ada lagi
     *   konsep "kirim balik untuk revisi" di sistem approval baru — reject di
     *   step manapun menutup siklus, verifikasi kembali ke Marketing untuk
     *   diedit & di-forward ulang sebagai siklus baru, lihat CA4/CA7)
     *
     * Status/badge tampilan derive dari document_approvals lewat
     * CustomerVerification::stageLabel()/CustomerController::resolveVerificationBadge(),
     * bukan kolom tersimpan.
     *
     * `bm_notes`/`bm_decision`/`bm_tgl_proses`/`bm_pic` TIDAK PERNAH ada di
     * skema `customer_verifications` maupun `$fillable` model -- `$cv->update()`
     * di baris ini silently no-op untuk keempat field itu (pre-existing bug).
     */
    public function bmVerify(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $r->validate([
            'notes'    => ['nullable', 'string'],
            'decision' => ['nullable', 'in:APPROVE,REJECT'],
        ]);

        return DB::transaction(function () use ($id, $data) {
            /** @var \App\Models\CustomerVerification $cv */
            $cv = CustomerVerification::lockForUpdate()->findOrFail($id);

            // default approval
            $decision = $data['decision'] ?? 'APPROVE';

            $cv->update([
                'bm_notes'      => $data['notes'] ?? null,
                'bm_decision'   => $decision,
                'bm_tgl_proses' => now(),
                'bm_pic'        => auth()->user()->name ?? null,
            ]);

            // ===== (CA6, pivot 2026-07-10, dulu C4/C5) sistem approval baru: document_approvals / document_approval_steps =====
            // BM adalah step FINAL di model 2-step baru (Marketing bukan
            // step formal lagi, Logistik & OM sudah dihapus dari alur, lihat
            // Prioritas D).
            //
            // (Prioritas H2, 2026-07-13) step_order literal `2` (dulu
            // hardcoded langsung, bukan bahkan lewat konstanta) sekarang
            // di-resolve dinamis: cari step di template aktif yang
            // id_role-nya cocok ROLE_BM. Kalau tidak ketemu, silent no-op +
            // Log::warning di kedua branch (APPROVE/REJECT) -- perilaku
            // defensif yang sama seperti saat advanceApprovalStep() tidak
            // menemukan approval/step in_progress, TIDAK ada guard/error
            // baru.
            $bmTemplate  = $this->activeApprovalTemplate();
            $bmStepOrder = $bmTemplate
                ? $this->resolveStepOrderForRole($bmTemplate, self::ROLE_BM)
                : null;

            if ($bmStepOrder === null) {
                Log::warning('Tidak menemukan step_order untuk role BM di template customer_verification aktif saat bmVerify.', [
                    'id_verification' => $cv->id_verification,
                    'decision'        => $decision,
                ]);
            }

            if ($decision === 'APPROVE') {
                if ($bmStepOrder !== null) {
                    $this->advanceApprovalStep(
                        $cv,
                        $bmStepOrder,
                        DocumentApprovalStepStatus::Approved,
                        $data['notes'] ?? null
                    );
                }

                // Badge "verified" derive langsung dari status document_approvals
                // milik latestVerification (lihat CustomerController::resolveVerificationBadge()) --
                // tidak ada flag tersimpan yang perlu di-set manual di sini.
            } else { // REJECT
                if ($bmStepOrder !== null) {
                    $this->advanceApprovalStep(
                        $cv,
                        $bmStepOrder,
                        DocumentApprovalStepStatus::Rejected,
                        $data['notes'] ?? null
                    );
                }
            }

            return response()->json(['ok' => true]);
        });
    }
}
