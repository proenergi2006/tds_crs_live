<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_confirmation_approval', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('id_sales')->index(); // FK → sales_confirmation.id

            // Admin
            $table->smallInteger('adm_result')->default(0); // 0=belum,1=ya,2=tidak
            $table->text('adm_summary')->nullable();
            $table->timestamp('adm_result_date')->nullable();
            $table->string('adm_pic', 255)->nullable();

            // BM
            $table->smallInteger('bm_result')->default(0);
            $table->text('bm_summary')->nullable();
            $table->timestamp('bm_result_date')->nullable();
            $table->string('bm_pic', 255)->nullable();

            // OM
            $table->smallInteger('om_result')->default(0);
            $table->text('om_summary')->nullable();
            $table->timestamp('om_result_date')->nullable();
            $table->string('om_pic', 255)->nullable();

            // Manager
            $table->smallInteger('mgr_result')->default(0);
            $table->text('mgr_summary')->nullable();
            $table->timestamp('mgr_result_date')->nullable();
            $table->string('mgr_pic', 255)->nullable();

            // CFO
            $table->smallInteger('cfo_result')->default(0);
            $table->text('cfo_summary')->nullable();
            $table->timestamp('cfo_result_date')->nullable();
            $table->string('cfo_pic', 255)->nullable();

            $table->timestamps();

            // FK → parent (hapus jika parent belum pasti)
            $table->foreign('id_sales')
                ->references('id')->on('sales_confirmation')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sales_confirmation_approval', function (Blueprint $table) {
            $table->dropForeign(['id_sales']);
        });
        Schema::dropIfExists('sales_confirmation_approval');
    }
};
