<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pasangan "Sebutkan" untuk vessel_quality_checking_method (kasus Other di
    // VesselQualityCheckingMethod), pola sama seperti vessel_type_other/
    // vessel_unloading_method_other/vessel_quantity_checking_method_other.
    public function up(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->string('vessel_quality_checking_method_other')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn('vessel_quality_checking_method_other');
        });
    }
};
