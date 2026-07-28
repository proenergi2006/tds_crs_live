<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'nib',
                'nib_file',
                'nomor_npwp',
                'nomor_npwp_file',
                'nomor_sertifikat',
                'nomor_sertifikat_file',
                'dokumen_lainnya',
                'dokumen_lainnya_file',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('nomor_sertifikat', 300)->nullable();
            $table->string('nomor_sertifikat_file', 300)->nullable();
            $table->string('nomor_npwp', 300)->nullable();
            $table->string('nomor_npwp_file', 300)->nullable();
            $table->string('dokumen_lainnya', 300)->nullable();
            $table->text('dokumen_lainnya_file')->nullable();
            $table->string('nib', 300)->nullable();
            $table->string('nib_file', 300)->nullable();
        });
    }
};
