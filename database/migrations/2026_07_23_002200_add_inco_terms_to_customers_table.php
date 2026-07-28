<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerIncoterm di model. Pindahan dari
            // customer_logistik.nico (company-wide declaration, bukan per-site).
            $table->string('inco_terms')->nullable()->after('induk_perusahaan');
            $table->string('inco_terms_other')->nullable()->after('inco_terms');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['inco_terms', 'inco_terms_other']);
        });
    }
};
