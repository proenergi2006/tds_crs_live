<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-C (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * `customer_logistic_claims` & `customer_lcr` ke DBML artifact `21a36fda`
 * (§12). Kedua tabel kosong (0 rows) -- zero risiko data, semua perubahan
 * murni DDL tanpa backfill.
 *
 * `customer_lcr.vessel_cargo_capacity` SENGAJA TIDAK diubah (tetap
 * varchar(100)) -- keputusan Engineer, DBML yang perlu dikoreksi di titik
 * ini, bukan kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_logistic_claims', function (Blueprint $table) {
            $table->unsignedBigInteger('id_customer')->change();
            $table->decimal('estimated_monthly_volume', 15, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_min', 10, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_max', 10, 2)->nullable()->change();
            $table->string('site_environment', 50)->nullable()->change();
            $table->string('site_environment_other', 255)->nullable()->change();
            $table->string('storage_type_other', 255)->nullable()->change();
            $table->string('operating_hours_other', 255)->nullable()->change();
            $table->string('quality_checking_method', 255)->nullable()->change();
            $table->string('quantity_checking_method', 255)->nullable()->change();
        });

        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->decimal('max_truck_capacity_min', 10, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_max', 10, 2)->nullable()->change();
            $table->decimal('max_loa', 10, 2)->nullable()->change();
            $table->decimal('min_pbl', 10, 2)->nullable()->change();
            $table->decimal('draft_lws', 10, 2)->nullable()->change();
            $table->decimal('jetty_capacity_dwt', 15, 2)->nullable()->change();
            $table->string('site_environment_other', 255)->nullable()->change();
            $table->string('storage_type_other', 255)->nullable()->change();
            $table->string('vessel_quantity_checking_method', 255)->nullable()->change();
            $table->string('vessel_quality_checking_method', 255)->nullable()->change();
            $table->string('site_business_type_other', 255)->nullable()->change();
            $table->string('unloading_method', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->string('unloading_method', 100)->nullable()->change();
            $table->text('site_business_type_other')->nullable()->change();
            $table->string('vessel_quality_checking_method', 100)->nullable()->change();
            $table->string('vessel_quantity_checking_method', 50)->nullable()->change();
            $table->string('storage_type_other', 100)->nullable()->change();
            $table->string('site_environment_other', 100)->nullable()->change();
            $table->decimal('jetty_capacity_dwt', 12, 2)->nullable()->change();
            $table->decimal('draft_lws', 8, 2)->nullable()->change();
            $table->decimal('min_pbl', 8, 2)->nullable()->change();
            $table->decimal('max_loa', 8, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_max', 8, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_min', 8, 2)->nullable()->change();
        });

        Schema::table('customer_logistic_claims', function (Blueprint $table) {
            $table->string('quantity_checking_method', 50)->nullable()->change();
            $table->string('quality_checking_method', 30)->nullable()->change();
            $table->string('operating_hours_other', 150)->nullable()->change();
            $table->string('storage_type_other', 150)->nullable()->change();
            $table->string('site_environment_other', 150)->nullable()->change();
            $table->string('site_environment', 30)->nullable()->change();
            $table->decimal('max_truck_capacity_max', 8, 2)->nullable()->change();
            $table->decimal('max_truck_capacity_min', 8, 2)->nullable()->change();
            $table->integer('estimated_monthly_volume')->nullable()->change();
            $table->integer('id_customer')->change();
        });
    }
};
