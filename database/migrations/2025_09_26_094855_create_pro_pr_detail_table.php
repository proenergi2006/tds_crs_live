<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pro_pr_detail', function (Blueprint $table) {
            // Engine & collation mengikuti DDL
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->increments('id_prd');
            $table->integer('id_pr')->nullable();
            $table->integer('id_plan')->nullable();

            $table->string('produk', 50)->nullable();
            $table->integer('volume')->nullable()->default(0);
            $table->integer('vol_potongan')->nullable()->default(0);
            $table->integer('vol_ket')->nullable();
            $table->integer('vol_ori')->default(0);

            $table->integer('transport')->nullable();
            $table->tinyInteger('pr_mobil')->default(1);

            $table->string('pr_top', 20)->nullable();
            $table->string('pr_actual_top', 50)->nullable();
            $table->string('pr_pelanggan', 30)->nullable();

            $table->integer('pr_ar_notyet')->nullable();
            $table->integer('pr_ar_satu')->nullable();
            $table->integer('pr_ar_dua')->nullable();

            $table->bigInteger('pr_kredit_limit')->nullable();

            $table->integer('pr_terminal')->nullable();
            $table->integer('pr_vendor')->nullable();

            $table->integer('pr_harga_beli')->nullable();
            $table->integer('pr_price_list')->default(0);

            $table->string('nomor_lo_pr', 80)->nullable();
            $table->string('schedule_payment', 200)->nullable();

            $table->tinyInteger('is_approved')->default(0);

            $table->integer('splitted_from_pr')->nullable();
            $table->integer('vol_ori_pr')->nullable();
            $table->integer('splitted_from')->nullable();

            $table->string('nomor_do_accurate', 80)->nullable();
            $table->string('no_do_acurate', 255)->nullable();
            $table->string('pr_po', 255)->nullable();
            $table->string('no_do_syop', 255)->nullable();

            $table->string('cabang', 10)->nullable();
            $table->string('nomor_po_supplier', 255)->nullable();
            $table->unsignedBigInteger('id_po_supplier')->nullable();
            $table->unsignedBigInteger('id_po_receive')->nullable();

            $table->integer('is_split')->nullable()->default(0);

            // Index seperti DDL
            $table->index('id_plan', 'pro_prd_idx1');
            $table->index('id_pr', 'pro_prd_idx2');
            $table->index('pr_vendor', 'pro_prd_idx3');
            $table->index('pr_terminal', 'pro_prd_idx4');

            // Foreign keys (pastikan tabel referensi tersedia)
            // FK ke pro_po_customer_plan(id_plan) - RESTRICT on delete, CASCADE on update
            $table->foreign('id_plan', 'pro_pr_detail_fk_plan')
            ->references('id_plan')->on('po_customer_plan')     // ✅ benar
            ->onDelete('restrict')->onUpdate('cascade');

            // FK ke pro_pr(id_pr) - CASCADE on delete & update
            $table->foreign('id_pr', 'pro_pr_detail_fk_pr')
                ->references('id_pr')->on('pro_pr')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pro_pr_detail', function (Blueprint $table) {
            // drop FKs dulu supaya aman di MySQL
            $table->dropForeign('pro_pr_detail_fk_plan');
            $table->dropForeign('pro_pr_detail_fk_pr');
        });

        Schema::dropIfExists('pro_pr_detail');
    }
};
