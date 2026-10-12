<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Mail\QuotationRejectedMail;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RejectQuotationAction
{
    public function execute(Quotation $quotation, User $actor, string $note, string $detailUrl): array
    {
        DB::beginTransaction();

        try {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            $step      = $quotation->activeVerificationStep();
            $verifier  = QuotationVerifierRole::tryFrom((int) $step?->id_role);

            $quotation->update(['status' => QuotationStatus::Rejected]);

            (new DocumentApprovalService())->decideStep($quotation, $step->step_order, DocumentApprovalStepStatus::Rejected, $actor->id, $note);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return ['success' => false, 'status' => 500, 'message' => 'Gagal menolak penawaran', 'error' => $e->getMessage()];
        }

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

        return ['message' => 'Status penawaran Ditolak'];
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
