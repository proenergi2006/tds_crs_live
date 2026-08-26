<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        // Administrator auto-lolos semua permission check, override hasPermissionTo() lama udah dihapus.
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->hasRole('Administrator')) {
                return true;
            }
        });

        // sentralisasi expiry di sini biar otomatis berlaku ke semua route auth:sanctum, gak perlu didaftar ulang per middleware
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $accessToken, bool $isValid) {
            if (! $isValid) {
                request()->attributes->set('token_expired_reason', 'session_expired');
                $accessToken->delete();

                return false;
            }

            return true;
        });
    }
}
