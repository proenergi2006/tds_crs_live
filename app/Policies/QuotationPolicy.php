<?php

namespace App\Policies;

use App\Enums\QuotationBrand;
use App\Enums\QuotationVerifierRole;
use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return QuotationBrand::viewableBy($user) !== [];
    }

    public function create(User $user): bool
    {
        return QuotationBrand::managedBy($user) !== [];
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $quotation->isViewableBy($user);
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $quotation->isManagedBy($user);
    }

    public function reopen(User $user, Quotation $quotation): bool
    {
        return false;
    }

    public function verify(User $user, Quotation $quotation): bool
    {
        $step = $quotation->activeVerificationStep();

        return $step !== null
            && QuotationVerifierRole::tryFrom((int) $step->id_role)?->isHeldBy($user, $quotation->brand) === true;
    }

    public function viewVerificationQueue(User $user): bool
    {
        return QuotationBrand::verifiableBy($user) !== [];
    }
}
