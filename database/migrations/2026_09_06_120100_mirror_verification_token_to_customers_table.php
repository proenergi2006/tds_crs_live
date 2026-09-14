<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('customer_verifications')
            ->whereNotNull('verification_token')
            ->orderBy('id_customer')
            ->orderBy('id_verification')
            ->get(['id_customer', 'id_verification', 'verification_token', 'expired_at'])
            ->keyBy('id_customer');

        foreach ($rows as $row) {
            DB::table('customers')
                ->where('id_customer', $row->id_customer)
                ->whereNull('onboarding_token')
                ->update([
                    'onboarding_token' => $row->verification_token,
                    'token_expired_at' => $row->expired_at,
                ]);
        }
    }

    // no-op disengaja: pasca-mirror, customers.onboarding_token tidak bisa dibedakan antara hasil mirror dan token terbitan flow baru, meng-null-kan balik berisiko menghapus token yang sah.
    public function down(): void
    {
    }
};
