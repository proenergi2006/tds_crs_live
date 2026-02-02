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
        Schema::table('vendors', function (Blueprint $table) {
            // 1) NPWP
            $table->string('npwp_number', 30)->nullable()->after('name'); // sesuaikan 'after' sesuai kolommu
            $table->string('npwp_file')->nullable()->after('npwp_number');

            // 2) NIB / TDP / SIUP
            $table->string('nib_number', 50)->nullable()->after('npwp_file');
            $table->string('nib_file')->nullable()->after('nib_number');

            // 3) SPPKP
            $table->string('sppkp_number', 50)->nullable()->after('nib_file');
            $table->string('sppkp_file')->nullable()->after('sppkp_number');

            // 4) Surat Pernyataan No Rekening / Rek Giro / Scan Buku Rekening
            $table->string('bank_account_letter_file')->nullable()->after('sppkp_file');

            // 5) Company Profile
            $table->string('company_profile_file')->nullable()->after('bank_account_letter_file');

            // Index yang berguna
            $table->index('npwp_number', 'vendors_npwp_number_idx');
            $table->index('nib_number', 'vendors_nib_number_idx');
            $table->index('sppkp_number', 'vendors_sppkp_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Drop index lebih dulu di sebagian driver
            $table->dropIndex('vendors_npwp_number_idx');
            $table->dropIndex('vendors_nib_number_idx');
            $table->dropIndex('vendors_sppkp_number_idx');

            $table->dropColumn([
                'npwp_number',
                'npwp_file',
                'nib_number',
                'nib_file',
                'sppkp_number',
                'sppkp_file',
                'bank_account_letter_file',
                'company_profile_file',
            ]);
        });
    }
};
