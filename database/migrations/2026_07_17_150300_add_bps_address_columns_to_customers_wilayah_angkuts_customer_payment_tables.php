<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->char('province_id', 2)->nullable()->after('id_kabupaten');
            $table->char('regency_id', 5)->nullable()->after('province_id');
            $table->char('district_id', 8)->nullable()->after('regency_id');
            $table->char('village_id', 13)->nullable()->after('district_id');

            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->char('province_id', 2)->nullable()->after('id_kabupaten');
            $table->char('regency_id', 5)->nullable()->after('province_id');
            $table->char('district_id', 8)->nullable()->after('regency_id');
            $table->char('village_id', 13)->nullable()->after('district_id');

            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->char('province_id', 2)->nullable()->after('kab_billing');
            $table->char('regency_id', 5)->nullable()->after('province_id');
            $table->char('district_id', 8)->nullable()->after('regency_id');
            $table->char('village_id', 13)->nullable()->after('district_id');

            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'regency_id', 'district_id', 'village_id']);
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'regency_id', 'district_id', 'village_id']);
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'regency_id', 'district_id', 'village_id']);
        });
    }
};
