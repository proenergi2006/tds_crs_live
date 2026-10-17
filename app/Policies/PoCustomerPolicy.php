<?php

namespace App\Policies;

use App\Enums\QuotationBrand;
use App\Models\PoCustomer;
use App\Models\Quotation;
use App\Models\User;

class PoCustomerPolicy
{
    private const ROLE_ADMIN_FINANCE = 9;

    public function viewAny(User $user): bool
    {
        return QuotationBrand::viewableAnyBy($user) !== []
            || QuotationBrand::managedBy($user) !== []
            || $user->can('sales-confirmation.manage');
    }

    public function create(User $user): bool
    {
        return QuotationBrand::managedBy($user) !== [];
    }

    public function createFrom(User $user, Quotation $quotation): bool
    {
        return $user->can($quotation->brand->managePermission());
    }

    public function view(User $user, PoCustomer $poCustomer): bool
    {
        return $poCustomer->isReadableBy($user);
    }

    public function update(User $user, PoCustomer $poCustomer): bool
    {
        return $poCustomer->isWritableBy($user);
    }

    public function delete(User $user, PoCustomer $poCustomer): bool
    {
        return $poCustomer->isWritableBy($user);
    }

    public function viewSalesConfirmation(User $user, PoCustomer $poCustomer): bool
    {
        return $user->canAny(SalesConfirmationPolicy::ACCESS_PERMISSIONS)
            && $poCustomer->isSalesConfirmationVisibleTo($user);
    }

    public function submitSalesConfirmation(User $user, PoCustomer $poCustomer): bool
    {
        return $user->can('sales-confirmation.manage')
            && (int) $user->primary_role_id === self::ROLE_ADMIN_FINANCE;
    }

    public function viewUnblockContext(User $user, PoCustomer $poCustomer): bool
    {
        return $user->can('sales-confirmation.manage');
    }

    public function decideSalesConfirmation(User $user, PoCustomer $poCustomer): bool
    {
        $sc = $poCustomer->salesConfirmation;

        return $user->can('sales-confirmation.approve')
            && $sc !== null
            && $sc->isDecidableBy($user);
    }
}
