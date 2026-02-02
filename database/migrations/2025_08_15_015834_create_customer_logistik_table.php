<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_logistik', function (Blueprint $table) {
            // match legacy options
            // $table->engine = 'InnoDB';
            // $table->charset = 'latin1';
            // $table->collation = 'latin1_swedish_ci';

            // PK (bukan auto-increment)
            $table->integer('id_customer')->primary();

            // fields
            $table->text('logistik_area');                 // NOT NULL
            $table->text('logistik_bisnis');               // NOT NULL

            $table->tinyInteger('logistik_env');           // NOT NULL
            $table->string('logistik_env_other', 300)->nullable();

            $table->tinyInteger('logistik_storage');       // NOT NULL
            $table->string('logistik_storage_other', 300)->nullable();

            $table->tinyInteger('logistik_hour');          // NOT NULL
            $table->string('logistik_hour_other', 300)->nullable();

            $table->tinyInteger('logistik_volume');        // NOT NULL
            $table->string('logistik_volume_other', 300)->nullable();

            $table->tinyInteger('logistik_quality');       // NOT NULL
            $table->string('logistik_quality_other', 300)->nullable();

            $table->tinyInteger('logistik_truck');         // NOT NULL
            $table->string('logistik_truck_other', 300)->nullable();

            $table->text('desc_stor_fac');                 // NOT NULL
            $table->text('desc_condition');                // NOT NULL

            // catatan: source pakai INT(2); di MySQL itu tetap 4 byte. 
            // Kalau cuma butuh 0–99 bisa ganti ke tinyInteger().
            $table->integer('supply_shceme')->nullable();  // ejaan mengikuti sumber (shceme)
            $table->integer('specify_product')->nullable();
            $table->integer('volume_per_month')->nullable();

            $table->string('operational_hour_from', 20)->nullable();
            $table->string('operational_hour_to', 20)->nullable();

            $table->integer('nico')->nullable();

            // FK
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
    }
};
