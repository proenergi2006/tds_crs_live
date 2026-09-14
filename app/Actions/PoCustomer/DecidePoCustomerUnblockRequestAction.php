<?php

namespace App\Actions\PoCustomer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PoCustomerScProcessState;
use App\Models\DocumentApproval;
use App\Models\PoCustomerUnblockRequest;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class DecidePoCustomerUnblockRequestAction
{
    public function __construct(private readonly DocumentApprovalService $approvalService)
    {
    }

    public function execute(
        PoCustomerUnblockRequest $unblockRequest,
        int $stepOrder,
        DocumentApprovalStepStatus $status,
        int $actorId,
        ?string $note,
        string $pic,
        ?string $ip
    ): ?DocumentApproval {
        return DB::transaction(function () use ($unblockRequest, $stepOrder, $status, $actorId, $note, $pic, $ip) {
            $cycle = $this->approvalService->decideStep($unblockRequest, $stepOrder, $status, $actorId, $note);

            if (! $cycle) {
                return null;
            }

            $unblockRequest->update(['status' => $cycle->status]);

            if ($cycle->status === DocumentApprovalStatus::Approved) {
                $unblockRequest->poCustomer->update([
                    'sc_process_state' => PoCustomerScProcessState::Cleared,
                    'lastupdate_time'  => now(),
                    'lastupdate_ip'    => $ip,
                    'lastupdate_by'    => $pic,
                ]);
            }

            return $cycle;
        });
    }
}
