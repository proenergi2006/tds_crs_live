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
        // Administrator (id_role=1) melewati semua permission check.
        //
        // URUTAN AKTUAL beforeCallbacks: [spatie-check, bypass-admin].
        // Spatie mendaftarkan callbacknya via callAfterResolving(Gate::class, ...).
        // Callback deferred itu terpicu tepat saat baris Gate::before() di bawah ini
        // meng-resolve Gate untuk pertama kali — sehingga Spatie masuk ke array LEBIH DULU,
        // baru kemudian callback admin bypass ini ditambahkan di posisi kedua.
        //
        // Konsekuensinya: untuk Administrator, Spatie's callback tetap berjalan duluan
        // dan memanggil User::hasPermissionTo() — yang sudah memiliki bypass id_role=1
        // di baris pertamanya (safety net utama). Callback ini berfungsi sebagai lapisan
        // kedua (defense in depth): mencegah Spatie berjalan untuk kasus Policy check
        // atau Gate check lain yang tidak melewati hasPermissionTo() sama sekali.
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->id_role === 1) {
                return true;
            }
        });

        // Sentralisasi idle-based + absolute-cap token expiry di titik validasi Sanctum
        // (bukan middleware per route group) supaya otomatis berlaku ke semua route
        // auth:sanctum tanpa didaftarkan ulang satu-satu.
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $accessToken, bool $isValid) {
            if (! $isValid) {
                request()->attributes->set('token_expired_reason', 'session_expired');
                $accessToken->delete();

                return false;
            }

            $idleMinutes = config('sanctum.idle_expiration');

            // last_used_at NULL berarti token belum pernah dipakai sejak diterbitkan —
            // treat sebagai "belum idle", bukan "idle sejak awal waktu".
            if ($accessToken->last_used_at && $accessToken->last_used_at->lt(now()->subMinutes($idleMinutes))) {
                request()->attributes->set('token_expired_reason', 'session_expired');
                $accessToken->delete();

                return false;
            }

            return true;
        });
    }
}
