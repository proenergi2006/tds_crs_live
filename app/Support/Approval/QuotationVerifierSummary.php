<?php

namespace App\Support\Approval;

use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Models\Quotation;
use App\Models\User;

class QuotationVerifierSummary
{
    private const INTERNAL_VIEW_PERMISSIONS = ['penawaran.verify', 'penawaran.verify-ceo', 'penawaran.proenergi.verify'];

    public static function canSeeInternals(User $user): bool
    {
        return $user->canAny(self::INTERNAL_VIEW_PERMISSIONS);
    }

    public static function for(Quotation $quotation, User $user): array
    {
        $cycle = $quotation->latestDocumentApproval;
        $step  = $quotation->activeVerificationStep();

        $displayStep = $step ?? $cycle?->steps->sortByDesc('step_order')->first();

        $canVerify = $quotation->status === QuotationStatus::WaitingVerification
            && $step !== null
            && QuotationVerifierRole::tryFrom((int) $step->id_role)?->isHeldBy($user) === true;

        return [
            'verifier_role' => $displayStep?->role?->name,
            'can_verify'    => $canVerify,
        ];
    }
}
