<?php

namespace App\Policies;

use App\Models\Cabang;
use App\Models\User;

class CabangPolicy
{
    private const MANAGE_PERMISSION = 'master-data.cabang.manage';

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cabang $cabang): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(self::MANAGE_PERMISSION);
    }

    public function update(User $user, Cabang $cabang): bool
    {
        return $user->can(self::MANAGE_PERMISSION);
    }

    public function delete(User $user): bool
    {
        return $user->can(self::MANAGE_PERMISSION);
    }
}
