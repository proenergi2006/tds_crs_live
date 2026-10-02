<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->dropForeign('wilayah_angkuts_district_id_foreign');
            $table->dropForeign('wilayah_angkuts_village_id_foreign');
            $table->dropColumn(['district_id', 'village_id']);
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->renameColumn('destinasi', 'name');
        });

        Schema::rename('wilayah_angkuts', 'transport_areas');
    }

    public function down(): void
    {
        Schema::rename('transport_areas', 'wilayah_angkuts');

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->renameColumn('name', 'destinasi');
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->char('district_id', 8)->nullable();
            $table->char('village_id', 13)->nullable();

            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });
    }
};
