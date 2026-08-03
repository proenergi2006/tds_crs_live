<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // website/survey_phone/survey_fax cuma duplikat customers.website/phone/fax (level company).
    // Kolom-kolom foto pindah ke customer_documents (id_lcr FK) supaya reuse infra
    // upload/approval dokumen yang udah ada. surveyor_names/competitors/operating_hours
    // diubah jadi scalar teks karena datanya memang gak repeatable. quality/quantity_checking_method
    // diubah jadi json array karena sekarang checkbox multi-select, bukan pilihan tunggal lagi.
    public function up(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn([
                'website', 'survey_phone', 'survey_fax',
                'road_condition_photos', 'site_layout_photos', 'unloading_layout_photos',
                'storage_facility_photos', 'measurement_evidence_photos', 'vessel_layout_photos',
                'company_office_photos', 'additional_photos',
                'surveyor_names', 'competitors', 'operating_hours',
                'quality_checking_method', 'quantity_checking_method',
            ]);
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->string('surveyor_names')->nullable();
            $table->string('competitors')->nullable();
            $table->string('operating_hours')->nullable();
            $table->json('quality_checking_method')->nullable();
            $table->json('quantity_checking_method')->nullable();
            $table->string('quality_checking_method_other')->nullable();
            $table->string('quantity_checking_method_other')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn([
                'surveyor_names', 'competitors', 'operating_hours',
                'quality_checking_method', 'quantity_checking_method',
                'quality_checking_method_other', 'quantity_checking_method_other',
            ]);
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->json('surveyor_names')->nullable();
            $table->json('competitors')->nullable();
            $table->json('operating_hours')->nullable();
            $table->string('quality_checking_method', 100)->nullable();
            $table->string('quantity_checking_method', 100)->nullable();
            $table->string('website', 191)->nullable();
            $table->string('survey_phone', 50)->nullable();
            $table->string('survey_fax', 50)->nullable();
            $table->json('road_condition_photos')->nullable();
            $table->json('site_layout_photos')->nullable();
            $table->json('unloading_layout_photos')->nullable();
            $table->json('storage_facility_photos')->nullable();
            $table->json('measurement_evidence_photos')->nullable();
            $table->json('vessel_layout_photos')->nullable();
            $table->json('company_office_photos')->nullable();
            $table->json('additional_photos')->nullable();
        });
    }
};
