<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn(['created_ip', 'lastupdate_ip']);
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->renameColumn('prov_survey', 'survey_province');
            $table->renameColumn('kab_survey', 'survey_regency');
            $table->renameColumn('telp_survey', 'survey_phone');
            $table->renameColumn('fax_survey', 'survey_fax');
            $table->renameColumn('latitude_lokasi', 'latitude');
            $table->renameColumn('longitude_lokasi', 'longitude');
            $table->renameColumn('link_google_maps', 'google_maps_link');
            $table->renameColumn('created_time', 'created_at');
            $table->renameColumn('lastupdate_time', 'updated_at');
            $table->renameColumn('lastupdate_by', 'updated_by');
        });

        // Postgres tidak bisa auto-cast varchar->numeric, dan kolom lama bisa berisi
        // string kosong (bukan NULL) -- perlu USING eksplisit dengan NULLIF.
        DB::statement("ALTER TABLE customer_lcr ALTER COLUMN storage_capacity TYPE NUMERIC(15,2) USING NULLIF(storage_capacity, '')::numeric(15,2)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customer_lcr ALTER COLUMN storage_capacity TYPE VARCHAR(100) USING storage_capacity::varchar(100)");

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->renameColumn('survey_province', 'prov_survey');
            $table->renameColumn('survey_regency', 'kab_survey');
            $table->renameColumn('survey_phone', 'telp_survey');
            $table->renameColumn('survey_fax', 'fax_survey');
            $table->renameColumn('latitude', 'latitude_lokasi');
            $table->renameColumn('longitude', 'longitude_lokasi');
            $table->renameColumn('google_maps_link', 'link_google_maps');
            $table->renameColumn('created_at', 'created_time');
            $table->renameColumn('updated_at', 'lastupdate_time');
            $table->renameColumn('updated_by', 'lastupdate_by');
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->string('created_ip', 45)->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
        });
    }
};
