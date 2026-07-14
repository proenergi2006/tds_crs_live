<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Redesain CustomerUpdateForm.vue mengganti SIUP/TDP dengan NIB (Nomor
     * Induk Berusaha) — SIUP/TDP sudah tidak lagi jadi persyaratan legalitas
     * standar di OSS sejak beberapa tahun terakhir, digantikan NIB. Sudah
     * digrep menyeluruh (app/ + resources/@client/) sebelum migration ini
     * dibuat: satu-satunya consumer kolom nomor_siup/nomor_siup_file/
     * nomor_tdp/nomor_tdp_file adalah CustomerUpdateForm.vue sendiri (dibaca
     * sebagai fallback saat prefill form) — tidak ada controller/PDF/laporan
     * lain yang membaca kolom ini, jadi aman didrop bersamaan dengan
     * penambahan kolom NIB.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('nib', 300)->nullable()->after('nomor_tdp_file');
            $table->string('nib_file', 300)->nullable()->after('nib');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_siup',
                'nomor_siup_file',
                'nomor_tdp',
                'nomor_tdp_file',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['nib', 'nib_file']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('nomor_siup', 300)->nullable();
            $table->string('nomor_siup_file', 300)->nullable();
            $table->string('nomor_tdp', 300)->nullable();
            $table->string('nomor_tdp_file', 300)->nullable();
        });
    }
};
