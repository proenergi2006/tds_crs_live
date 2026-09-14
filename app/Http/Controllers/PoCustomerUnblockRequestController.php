<?php

namespace App\Http\Controllers;

use App\Actions\PoCustomer\DecidePoCustomerUnblockRequestAction;
use App\Actions\PoCustomer\SubmitPoCustomerUnblockRequestAction;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PoCustomerScProcessState;
use App\Http\Requests\PoCustomer\DecidePoCustomerUnblockRequestRequest;
use App\Http\Requests\PoCustomer\StorePoCustomerUnblockRequestRequest;
use App\Models\PoCustomer;
use App\Models\PoCustomerUnblockRequest;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PoCustomerUnblockRequestController extends Controller
{
    private const APPROVAL_TEMPLATE_CODE = 'po_customer_unblock';
    private const ROLE_ADMIN_FINANCE = 9;
    private const ROLE_BM = 8;
    private const PPN_RATE = 0.11;

    private const APPROVAL_STEP_RELATIONS = [
        'requestedBy:id,name,primary_role_id',
        'requestedBy.primaryRole:id,name',
        'latestDocumentApproval.steps.templateStep:id_step,step_name,step_order,id_role',
        'latestDocumentApproval.steps.actor:id,name',
    ];

    public function __construct(private readonly DocumentApprovalService $approvalService)
    {
    }

    public function store(StorePoCustomerUnblockRequestRequest $request, int $idPoc, SubmitPoCustomerUnblockRequestAction $action): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->cant('sales-confirmation.manage') || (int) $user->primary_role_id !== self::ROLE_ADMIN_FINANCE) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $po = PoCustomer::findOrFail($idPoc);

        if ($po->sc_process_state !== PoCustomerScProcessState::Blocked) {
            return response()->json(['message' => 'Unblock hanya untuk PO yang diblokir kredit.'], 409);
        }

        if ($po->activeUnblockRequest() !== null || $po->approvedUnblockRequest() !== null) {
            return response()->json(['message' => 'Sudah ada pengajuan Unblock aktif / disetujui untuk PO ini.'], 409);
        }

        $unblockRequest = $action->execute(
            $po,
            $request->validated(),
            $request->file('attachments', []),
            $user->id,
            $user->name ?? 'system',
            $request->ip()
        );

        $unblockRequest->load(self::APPROVAL_STEP_RELATIONS);

        return response()->json($this->formatUnblockRequest($unblockRequest), 201);
    }

    public function unblockContext(Request $request, int $idPoc): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('sales-confirmation.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $po = PoCustomer::with([
            'customer:id_customer,customer_code,company_name',
            'customer.latestApprovedVerification',
            'customer.adminArnya',
        ])->findOrFail($idPoc);

        $activeRequest = $po->activeUnblockRequest();
        $activeRequest?->load(self::APPROVAL_STEP_RELATIONS);

        return response()->json([
            'po' => array_merge($this->formatPoSummary($po), [
                'status_key'   => $po->status_key,
                'status_label' => $po->status_label,
            ]),
            'active_request' => $activeRequest ? $this->formatUnblockRequest($activeRequest) : null,
        ]);
    }

    public function decide(DecidePoCustomerUnblockRequestRequest $request, PoCustomerUnblockRequest $unblockRequest, DecidePoCustomerUnblockRequestAction $action): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($user->cant('sales-confirmation.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $roleId = (int) $user->primary_role_id;
        if (! in_array($roleId, [self::ROLE_ADMIN_FINANCE, self::ROLE_BM], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $template  = $this->approvalService->activeTemplate(self::APPROVAL_TEMPLATE_CODE);
        $stepOrder = $template ? $this->approvalService->resolveStepOrderForRole($template, $roleId) : null;

        if ($stepOrder === null) {
            return response()->json(['message' => 'Approval template po_customer_unblock belum ter-setup dengan benar.'], 422);
        }

        if ($unblockRequest->status !== DocumentApprovalStatus::InProgress) {
            return response()->json(['message' => 'Pengajuan Unblock ini sudah selesai.'], 409);
        }

        $cycle = $this->approvalService->activeCycle($unblockRequest);
        if (! $cycle) {
            return response()->json(['message' => 'Pengajuan Unblock ini sudah selesai.'], 409);
        }

        if ((int) $cycle->current_step_order !== $stepOrder) {
            return response()->json(['message' => 'Belum giliran Anda memutuskan.'], 409);
        }

        $status = $request->validated()['decision'] === 'approve'
            ? DocumentApprovalStepStatus::Approved
            : DocumentApprovalStepStatus::Rejected;

        $result = $action->execute(
            $unblockRequest,
            $stepOrder,
            $status,
            $user->id,
            $request->validated()['note'] ?? null,
            $user->name ?? 'system',
            $request->ip()
        );

        if (! $result) {
            return response()->json(['message' => 'Tidak ada siklus approval aktif untuk pengajuan Unblock ini.'], 409);
        }

        $unblockRequest->refresh()->load(self::APPROVAL_STEP_RELATIONS);
        $po = $unblockRequest->poCustomer()->first();

        return response()->json(array_merge($this->formatUnblockRequest($unblockRequest), [
            'po' => [
                'id_poc'           => $po->id_poc,
                'sc_process_state' => $po->sc_process_state?->value,
                'status_key'       => $po->status_key,
                'status_label'     => $po->status_label,
            ],
        ]));
    }

    private function formatUnblockRequest(PoCustomerUnblockRequest $unblockRequest): array
    {
        $cycle = $unblockRequest->latestDocumentApproval;

        return [
            'id'           => $unblockRequest->id,
            'id_poc'       => $unblockRequest->id_poc,
            'reason'       => $unblockRequest->reason,
            'attachments'  => $this->formatAttachments($unblockRequest->attachments ?? []),
            'status'       => $unblockRequest->status?->value,
            'status_label' => $unblockRequest->status?->label(),
            'requested_by' => $unblockRequest->requestedBy
                ? [
                    'id'        => $unblockRequest->requestedBy->id,
                    'name'      => $unblockRequest->requestedBy->name,
                    'role_name' => $unblockRequest->requestedBy->primaryRole?->name,
                ]
                : null,
            'created_at'   => optional($unblockRequest->created_at)->toISOString(),
            'approval'     => $cycle ? [
                'current_step_order' => $cycle->current_step_order,
                'steps'              => $cycle->steps->sortBy('step_order')->map(fn ($step) => [
                    'step_order'    => $step->step_order,
                    'step_name'     => $step->templateStep->step_name ?? null,
                    'status'        => $step->status?->value,
                    'actor_name'    => $step->actor->name ?? null,
                    'acted_at'      => optional($step->acted_at)->toISOString(),
                    'decision_note' => $step->decision_note,
                ])->values(),
            ] : null,
        ];
    }

    private function formatAttachments(array $attachments): array
    {
        return array_map(function (array $attachment) {
            $path = $attachment['path'] ?? null;
            $exists = $path && Storage::disk('public')->exists($path);

            return array_merge($attachment, [
                'size_bytes' => $exists ? Storage::disk('public')->size($path) : null,
            ]);
        }, $attachments);
    }

    private function formatPoSummary(?PoCustomer $po): array
    {
        return [
            'id_poc'    => $po?->id_poc,
            'nomor_poc' => $po?->nomor_poc,
            'customer'  => [
                'customer_code' => $po?->customer?->customer_code,
                'company_name'  => $po?->customer?->company_name,
            ],
            'nilai_order'          => round((float) ($po?->harga_poc ?? 0) * (float) ($po?->volume_poc ?? 0) * (1 + self::PPN_RATE), 2),
            'current_credit_limit' => (float) ($po?->customer?->current_credit_limit ?? 0),
            'exposure'             => (float) ($po?->customer?->adminArnya?->total_ar ?? 0),
            'headroom'             => (float) ($po?->customer?->current_credit_limit ?? 0) - (float) ($po?->customer?->adminArnya?->total_ar ?? 0),
        ];
    }
}
