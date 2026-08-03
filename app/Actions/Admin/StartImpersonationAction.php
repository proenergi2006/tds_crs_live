<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Support\ImpersonationToken;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class StartImpersonationAction
{
    public function execute(User $admin, PersonalAccessToken $adminToken, User $target, string $ip): array
    {
        if (ImpersonationToken::decode($adminToken->name) !== null) {
            abort(409, 'Sedang impersonate user lain, kembali ke admin dulu');
        }

        if ($target->id === $admin->id) {
            abort(422, 'Tidak bisa impersonate diri sendiri');
        }

        if ($target->id_role === 1) {
            abort(403, 'Tidak bisa impersonate sesama admin');
        }

        if (! $target->is_active) {
            abort(403, 'User nonaktif tidak bisa di-impersonate');
        }

        $expiresAt = now()->addHours(2);

        $newToken = $target->createToken(ImpersonationToken::encode($admin->id), ['*'], $expiresAt);

        $adminToken->delete();

        Log::warning('impersonation.start', [
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->name,
            'target_id'   => $target->id,
            'target_name' => $target->name,
            'ip'          => $ip,
            'at'          => now()->toISOString(),
        ]);

        return [
            'access_token' => $newToken->plainTextToken,
            'user'         => $target,
            'admin'        => ['id' => $admin->id, 'name' => $admin->name],
            'started_at'   => now()->toISOString(),
            'expires_at'   => $expiresAt->toISOString(),
        ];
    }
}
