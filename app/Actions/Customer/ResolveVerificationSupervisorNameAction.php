<?php

namespace App\Actions\Customer;

use App\Enums\CreditProductCategory;
use App\Models\CustomerVerification;
use App\Models\User;

class ResolveVerificationSupervisorNameAction
{
    private const ROLE_BRANCH_MANAGER_TDS = 8;
    private const ROLE_MARKETING_PROENERGI = 13;
    private const ROLE_KAE_PROENERGI = 14;
    private const ROLE_BRANCH_MANAGER_PROENERGI = 15;
    private const ROLE_BRANCH_MANAGER_POLIMER = 17;

    public function execute(CustomerVerification $verification): string
    {
        $supervisor = User::role($this->resolveRoleId($verification))
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return $supervisor?->name ?? '-';
    }

    private function resolveRoleId(CustomerVerification $verification): int
    {
        if ($verification->product_category_snapshot === CreditProductCategory::Polimer) {
            return self::ROLE_BRANCH_MANAGER_POLIMER;
        }

        $owner = $verification->customer->loadMissing('user')->user;

        if ($owner?->hasAnyRole([self::ROLE_MARKETING_PROENERGI, self::ROLE_KAE_PROENERGI])) {
            return self::ROLE_BRANCH_MANAGER_PROENERGI;
        }

        return self::ROLE_BRANCH_MANAGER_TDS;
    }
}
