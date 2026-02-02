<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pro_pr', function (Blueprint $table) {
            // Engine & collation mengikuti DDL
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->increments('id_pr');
            $table->integer('id_wilayah')->nullable();
            $table->integer('id_group')->nullable();
            $table->string('nomor_pr', 50)->nullable();
            $table->date('tanggal_pr')->nullable();

            $table->tinyInteger('logistik_result')->default(0);
            $table->string('logistik_pic', 80)->nullable();
            $table->dateTime('logistik_tanggal')->nullable();

            $table->tinyInteger('finance_result')->default(0);
            $table->string('finance_pic', 80)->nullable();
            $table->dateTime('finance_tanggal')->nullable();

            $table->tinyInteger('sm_result')->default(0);
            $table->string('sm_pic', 80)->nullable();
            $table->dateTime('sm_tanggal')->nullable();
            $table->text('sm_summary')->nullable();

            $table->tinyInteger('purchasing_result')->default(0);
            $table->string('purchasing_pic', 80)->nullable();
            $table->dateTime('purchasing_tanggal')->nullable();
            $table->text('purchasing_summary')->nullable();

            $table->tinyInteger('cfo_result')->default(0);
            $table->string('cfo_pic', 80)->nullable();
            $table->dateTime('cfo_tanggal')->nullable();
            $table->text('cfo_summary')->nullable();

            $table->tinyInteger('ceo_result')->default(0);
            $table->string('ceo_pic', 80)->nullable();
            $table->dateTime('ceo_tanggal')->nullable();
            $table->text('ceo_summary')->nullable();

            $table->tinyInteger('is_ceo')->default(0);
            $table->tinyInteger('revert_cfo')->default(0);
            $table->text('revert_cfo_summary')->nullable();
            $table->tinyInteger('revert_ceo')->default(0);
            $table->text('revert_ceo_summary')->nullable();

            $table->tinyInteger('disposisi_pr')->default(0);
            $table->tinyInteger('ada_ar')->default(0);
            $table->tinyInteger('ar_approved')->default(0);
            $table->tinyInteger('is_edited')->default(0);

            $table->tinyInteger('coo_result')->nullable()->default(0);
            $table->string('coo_pic', 80)->nullable();
            $table->text('coo_summary')->nullable();
            $table->dateTime('coo_tanggal')->nullable();

            $table->dateTime('jam_submit')->nullable();
            $table->dateTime('submit_bm')->nullable();

            $table->integer('pr_con')->nullable();
            $table->string('lampiran_con', 255)->nullable();
            $table->string('lampiran_con_ori', 255)->nullable();

            // index gabungan seperti DDL
            $table->index(['id_wilayah', 'id_group'], 'pro_pr_idx1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_pr');
    }
};
