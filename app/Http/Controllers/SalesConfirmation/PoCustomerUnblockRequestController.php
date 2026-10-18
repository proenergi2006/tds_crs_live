<?php

namespace App\Http\Controllers\SalesConfirmation;

use App\Actions\PoCustomer\SubmitPoCustomerUnblockRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PoCustomer\StorePoCustomerUnblockRequestRequest as StoreRequest;
use App\Http\Responses\ApiResponse;
use App\Models\PoCustomer;
use App\Models\PoCustomerUnblockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PoCustomerUnblockRequestController extends Controller
{
    private const APPROVAL_STEP_RELATIONS = [
        'requestedBy:id,name,primary_role_id',
        'requestedBy.primaryRole:id,name',
        'latestDocumentApproval.steps.templateStep:id_step,step_name,step_order,id_role',
        'latestDocumentApproval.steps.actor:id,name',
    ];

    public function store(
        StoreRequest $request,
        PoCustomer $poc,
        SubmitPoCustomerUnblockRequestAction $action
    ): JsonResponse {
        $this->authorize('submitSalesConfirmation', $poc);

        $user = $request->user();

        $unblockRequest = $action->execute(
            $poc,
            $request->validated(),
            $request->file('attachments', []),
            $user->id,
            $user->name ?? 'system',
            $request->ip()
        );

        $unblockRequest->load(self::APPROVAL_STEP_RELATIONS);

        return ApiResponse::success(
            $this->formatUnblockRequest($unblockRequest),
            'Pengajuan Unblock berhasil dikirim.',
            201
        );
    }

    public function unblockContext(Request $request, PoCustomer $poc): JsonResponse
    {
        $this->authorize('viewUnblockContext', $poc);

        $po = $poc->load([
            'customer:id_customer,customer_code,company_name',
            'customer.latestApprovedVerification',
            'customer.adminArnya',
            'items',
        ]);

        $activeRequest = $po->activeUnblockRequest();
        $activeRequest?->load(self::APPROVAL_STEP_RELATIONS);

        return ApiResponse::success(
            [
                'po' => array_merge($this->formatPoSummary($po), [
                    'status_key'   => $po->status_key,
                    'status_label' => $po->status_label,
                ]),
                'active_request' => $activeRequest ? $this->formatUnblockRequest($activeRequest) : null,
            ],
            'Konteks Unblock berhasil dimuat.'
        );
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
        $creditLimit = (float) ($po?->customer?->current_credit_limit ?? 0);
        $exposure    = (float) ($po?->customer?->adminArnya?->total_ar ?? 0);

        return [
            'id_poc'    => $po?->id_poc,
            'nomor_poc' => $po?->nomor_poc,
            'customer'  => [
                'customer_code' => $po?->customer?->customer_code,
                'company_name'  => $po?->customer?->company_name,
            ],
            'nilai_order'          => $po?->orderValueWithPpn() ?? 0.0,
            'current_credit_limit' => $creditLimit,
            'exposure'             => $exposure,
            'headroom'             => $creditLimit - $exposure,
        ];
    }
}
