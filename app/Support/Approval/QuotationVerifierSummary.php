<?php

namespace App\Support\Approval;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationBrand;
use App\Models\Quotation;
use App\Models\User;

class QuotationVerifierSummary
{
    public static function canSeeInternals(User $user): bool
    {
        return $user->canAny(array_map(fn (QuotationBrand $brand) => $brand->verifyPermission(), QuotationBrand::cases()));
    }

    public static function for(Quotation $quotation, User $user): array
    {
        $cycle = $quotation->latestDocumentApproval;
        $step  = $quotation->activeVerificationStep();

        $displayStep = $step ?? $cycle?->steps
            ->reject(fn ($s) => $s->status === DocumentApprovalStepStatus::Cancelled)
            ->sortByDesc('step_order')
            ->first();

        $canVerify = $quotation->isAwaitingVerification() && $user->can('verify', $quotation);

        return [
            'verifier_role' => $displayStep?->role?->name,
            'can_verify'    => $canVerify,
        ];
    }
}
