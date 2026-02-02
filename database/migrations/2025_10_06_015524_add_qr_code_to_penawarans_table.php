<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            // simpan payload/URL QR (pakai text biar bebas panjang)
            $table->text('qr_code')->nullable()->after('nomor_penawaran');
            // opsional: meta bantu
            // $table->string('qr_hash', 64)->nullable()->unique()->after('qr_code');
            // $table->timestampTz('qr_generated_at')->nullable()->after('qr_hash');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['qr_code']); // tambah kolom lain di atas? drop di sini juga
        });
    }
};
