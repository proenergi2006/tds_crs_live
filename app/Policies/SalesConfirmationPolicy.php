<?php

namespace App\Policies;

use App\Models\User;

class SalesConfirmationPolicy
{
    public const ACCESS_PERMISSIONS = [
        'sales-confirmation.manage',
        'sales-confirmation.view',
        'sales-confirmation.approve',
    ];

    public function viewAny(User $user): bool
    {
        return $user->canAny(self::ACCESS_PERMISSIONS);
    }
}
