<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Exceptions\BusinessRuleException;
use App\Mail\QuotationRejectedMail;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RejectQuotationAction
{
    public function execute(Quotation $quotation, User $actor, string $note, string $detailUrl): void
    {
        [$quotation, $verifier] = DB::transaction(function () use ($quotation, $actor, $note) {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            $step      = $quotation->activeVerificationStep();

            if (! $step) {
                throw new BusinessRuleException('Penawaran tidak sedang menunggu verifikasi.');
            }

            $verifier = QuotationVerifierRole::tryFrom((int) $step->id_role);

            $quotation->update(['status' => QuotationStatus::Rejected]);

            (new DocumentApprovalService())->decideStep($quotation, $step->step_order, DocumentApprovalStepStatus::Rejected, $actor->id, $note);

            return [$quotation, $verifier];
        });

        $creatorEmail = $this->getCreatorEmail($quotation->created_by);

        if ($creatorEmail) {
            try {
                Mail::to($creatorEmail)->send(
                    new QuotationRejectedMail($quotation, $detailUrl, 'Ditolak oleh ' . ($verifier?->label() ?? 'verifikator'), $note)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function getCreatorEmail(?string $creatorName): ?string
    {
        if (! $creatorName) {
            return null;
        }

        $query = User::where('name', $creatorName)->whereNotNull('email');

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->first()?->email;
    }
}
