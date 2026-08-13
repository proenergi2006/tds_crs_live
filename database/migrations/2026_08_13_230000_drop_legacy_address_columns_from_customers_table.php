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
                'id_provinsi',
                'id_kabupaten',
                'province_id',
                'regency_id',
                'district_id',
                'village_id',
                'customer_sub_district',
                'customer_village',
                'postal_code',
                'company_address',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('id_provinsi')->nullable();
            $table->unsignedBigInteger('id_kabupaten')->nullable();
            $table->char('province_id', 2)->nullable();
            $table->char('regency_id', 5)->nullable();
            $table->char('district_id', 8)->nullable();
            $table->char('village_id', 13)->nullable();
            $table->string('customer_sub_district', 255)->nullable();
            $table->string('customer_village', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('company_address')->nullable();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('id_provinsi')->references('id_provinsi')->on('provinsis')->onDelete('restrict');
            $table->foreign('id_kabupaten')->references('id_kabupaten')->on('kabupatens')->onDelete('restrict');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });
    }
};
