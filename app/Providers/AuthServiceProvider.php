<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
    }
}
