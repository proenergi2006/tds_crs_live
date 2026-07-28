<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rebuild total `customer_logistik`: tetap 1:1 per customer (bukan
     * per-site seperti `customer_lcr`), diselaraskan taksonomi dengan
     * `customer_lcr` rev. 3 (site_environment/storage_type/dst). Tabel live
     * cuma punya 1 baris dummy/test (id_customer=73) saat migration ini
     * ditulis, jadi tidak ada migrasi data.
     */
    public function up(): void
    {
        Schema::dropIfExists('customer_logistik');

        Schema::create('customer_logistik', function (Blueprint $table) {
            $table->integer('id_customer')->primary();

            $table->string('site_environment', 30)->nullable();
            $table->string('site_environment_other', 150)->nullable();
            $table->text('site_environment_notes')->nullable();

            $table->string('storage_type', 30)->nullable();
            $table->string('storage_type_other', 150)->nullable();
            $table->text('storage_notes')->nullable();

            $table->string('operating_hours', 30)->nullable();
            $table->string('operating_hours_other', 150)->nullable();

            $table->string('quality_checking_method', 30)->nullable();
            $table->text('quality_checking_notes')->nullable();

            $table->string('quantity_checking_method', 50)->nullable();
            $table->text('quantity_checking_notes')->nullable();

            $table->decimal('max_truck_capacity_min', 8, 2)->nullable();
            $table->decimal('max_truck_capacity_max', 8, 2)->nullable();

            $table->boolean('supports_vessel_delivery')->default(false);

            $table->text('product_notes')->nullable();
            $table->integer('estimated_monthly_volume')->nullable();

            // Jam terima/kirim spesifik (Supply Scheme), beda konsep dari
            // operating_hours di atas (jam operasional facility) -- tetap
            // dipertahankan terpisah, aktif dipakai FE (form.supply).
            $table->string('operational_hour_from', 20)->nullable();
            $table->string('operational_hour_to', 20)->nullable();

            $table->dateTime('created_time')->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
            $table->string('lastupdate_by', 100)->nullable();

            $table->foreign('id_customer')
                ->references('id_customer')
                ->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_logistik');

        Schema::create('customer_logistik', function (Blueprint $table) {
            $table->integer('id_customer')->primary();

            $table->text('logistik_area');
            $table->text('logistik_bisnis');

            $table->tinyInteger('logistik_env');
            $table->string('logistik_env_other', 300)->nullable();

            $table->tinyInteger('logistik_storage');
            $table->string('logistik_storage_other', 300)->nullable();

            $table->tinyInteger('logistik_hour');
            $table->string('logistik_hour_other', 300)->nullable();

            $table->tinyInteger('logistik_volume');
            $table->string('logistik_volume_other', 300)->nullable();

            $table->tinyInteger('logistik_quality');
            $table->string('logistik_quality_other', 300)->nullable();

            $table->tinyInteger('logistik_truck');
            $table->string('logistik_truck_other', 300)->nullable();

            $table->text('desc_stor_fac');
            $table->text('desc_condition');

            $table->integer('supply_shceme')->nullable();
            $table->integer('specify_product')->nullable();
            $table->integer('volume_per_month')->nullable();

            $table->string('operational_hour_from', 20)->nullable();
            $table->string('operational_hour_to', 20)->nullable();

            $table->integer('nico')->nullable();

            $table->foreign('id_customer')
                ->references('id_customer')
                ->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
