<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regencies', function (Blueprint $table) {
            $table->index('province_id');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->index('regency_id');
            $table->index('province_id');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->index('district_id');
            $table->index('regency_id');
            $table->index('province_id');
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('regencies', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('province_id');
            $table->index('regency_id');
            $table->index('district_id');
            $table->index('village_id');
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->index('province_id');
            $table->index('regency_id');
            $table->index('district_id');
            $table->index('village_id');
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->index('province_id');
            $table->index('regency_id');
            $table->index('district_id');
            $table->index('village_id');
        });
    }

    public function down(): void
    {
        Schema::table('regencies', function (Blueprint $table) {
            $table->dropIndex(['province_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['province_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->dropIndex(['district_id']);
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['province_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['province_id']);
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['district_id']);
            $table->dropIndex(['village_id']);
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->dropIndex(['province_id']);
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['district_id']);
            $table->dropIndex(['village_id']);
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->dropIndex(['province_id']);
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['district_id']);
            $table->dropIndex(['village_id']);
        });
    }
};
