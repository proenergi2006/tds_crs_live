<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            // Kapan token verifikasi ini kedaluwarsa. Dihitung sekali saat token
            // dibuat (now()->addDays(7)) di LinkCustomerController::generate(),
            // bukan dihitung runtime dari kolom lain.
            $table->timestamp('expired_at')->nullable()->after('is_active');

            // Status kelengkapan pengisian form oleh customer. Terpisah dari
            // is_evaluated (yang semantiknya milik proses evaluasi internal
            // BM/OM) — kolom ini murni menandai apakah customer sudah submit
            // form via link publik atau belum. Default 'draft' supaya baris
            // lama (belum submit) otomatis dianggap belum submit.
            // String (bukan native enum) + PHP backed enum di App\Enums,
            // sesuai konvensi kolom status baru di project ini.
            $table->string('completion_status', 20)->default('draft')->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn(['expired_at', 'completion_status']);
        });
    }
};
