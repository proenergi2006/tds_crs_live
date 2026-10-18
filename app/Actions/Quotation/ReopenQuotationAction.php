<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStatus;
use App\Enums\QuotationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class ReopenQuotationAction
{
    public function __construct(private readonly DocumentApprovalService $approvalService)
    {
    }

    public function execute(Quotation $quotation, User $actor, string $note): void
    {
        DB::transaction(function () use ($quotation, $actor, $note) {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);

            if (! $quotation->isReopenableStatus()) {
                throw new BusinessRuleException('Hanya penawaran yang disetujui atau sedang diverifikasi yang bisa dikembalikan ke Draft.');
            }

            if (! $quotation->isValidOn(now())) {
                throw new BusinessRuleException('Masa berlaku penawaran sudah lewat, buat penawaran baru dari penawaran ini.');
            }

            if ($quotation->poCustomers()->exists()) {
                throw new BusinessRuleException('Penawaran sudah dipakai di PO Customer, tidak bisa dikembalikan ke Draft.');
            }

            $roleId  = $actor->roles->firstWhere('name', 'Administrator')?->id;
            $cycle  = $quotation->latestDocumentApproval;

            if ($cycle && in_array($cycle->status, [DocumentApprovalStatus::InProgress, DocumentApprovalStatus::Approved], true)) {
                $this->approvalService->cancelCycle($cycle, $actor->id, $roleId, $note);
            } else {
                $this->approvalService->startCancelledCycle($quotation, $actor->id, $roleId, $note);
            }

            $quotation->update([
                'status'     => QuotationStatus::Draft,
                'updated_at' => now(),
            ]);
        });
    }
}
