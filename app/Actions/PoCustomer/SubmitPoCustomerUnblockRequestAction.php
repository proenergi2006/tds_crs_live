<?php

namespace App\Actions\PoCustomer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\PoCustomer;
use App\Models\PoCustomerUnblockRequest;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmitPoCustomerUnblockRequestAction
{
    private const APPROVAL_TEMPLATE_CODE = 'po_customer_unblock';

    public function __construct(
        private readonly DocumentApprovalService $approvalService,
        private readonly DecidePoCustomerUnblockRequestAction $decideAction
    ) {
    }

    public function execute(PoCustomer $po, array $data, array $files, int $userId, string $pic, ?string $ip): PoCustomerUnblockRequest
    {
        $stored = [];

        foreach ($files as $file) {
            $path = $file->storeAs(
                "unblock-requests/{$po->id_poc}",
                Str::random(20) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $stored[] = ['path' => $path, 'original_name' => $file->getClientOriginalName()];
        }

        return DB::transaction(function () use ($po, $data, $stored, $userId, $pic, $ip) {
            $unblockRequest = PoCustomerUnblockRequest::create([
                'id_poc'       => $po->id_poc,
                'reason'       => $data['reason'] ?? null,
                'attachments'  => $stored,
                'status'       => DocumentApprovalStatus::InProgress,
                'requested_by' => $userId,
            ]);

            $this->approvalService->startCycle($unblockRequest, self::APPROVAL_TEMPLATE_CODE);

            $this->decideAction->execute(
                $unblockRequest,
                1,
                DocumentApprovalStepStatus::Approved,
                $userId,
                null,
                $pic,
                $ip
            );

            return $unblockRequest;
        });
    }
}
