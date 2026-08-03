<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Support\ImpersonationToken;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class LeaveImpersonationAction
{
    public function execute(User $impersonatedUser, PersonalAccessToken $currentToken, string $ip): array
    {
        $adminId = ImpersonationToken::decode($currentToken->name);

        if ($adminId === null) {
            abort(422, 'Token ini bukan sesi impersonasi');
        }

        $admin = User::find($adminId);

        if (! $admin || $admin->id_role !== 1 || ! $admin->is_active) {
            abort(403, 'Sesi admin sudah tidak valid, silakan login ulang');
        }

        $adminToken = $admin->createToken('api_token', ['*'], now()->addYear());

        $currentToken->delete();

        Log::warning('impersonation.leave', [
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->name,
            'target_id'   => $impersonatedUser->id,
            'target_name' => $impersonatedUser->name,
            'ip'          => $ip,
            'at'          => now()->toISOString(),
        ]);

        return [
            'access_token' => $adminToken->plainTextToken,
            'admin'        => $admin,
        ];
    }
}
