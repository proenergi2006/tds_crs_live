<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // vessel_quality/quantity_checking_method diubah jadi json array, ngikutin pola checking
    // method non-vessel (checkbox multi-select). Kolom _other adalah pasangan "Sebutkan"
    // untuk pilihan Other di vessel_type/vessel_unloading_method/vessel_quantity_checking_method.
    public function up(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn(['vessel_quality_checking_method', 'vessel_quantity_checking_method']);
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->json('vessel_quality_checking_method')->nullable();
            $table->json('vessel_quantity_checking_method')->nullable();
            $table->string('vessel_type_other')->nullable();
            $table->string('vessel_unloading_method_other')->nullable();
            $table->string('vessel_quantity_checking_method_other')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn([
                'vessel_quality_checking_method', 'vessel_quantity_checking_method',
                'vessel_type_other', 'vessel_unloading_method_other', 'vessel_quantity_checking_method_other',
            ]);
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->string('vessel_quality_checking_method', 255)->nullable();
            $table->string('vessel_quantity_checking_method', 255)->nullable();
        });
    }
};
