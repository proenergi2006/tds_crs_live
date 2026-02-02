<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_verifications', function (Blueprint $table) {
            // PK
            $table->increments('id_verification');

            // FK ke customers.id_customer
            // (pakai integer agar aman untuk PostgreSQL/MySQL yg kolom induknya int/increments)
            $table->integer('id_customer');
            $table->index('id_customer', 'customer_ver_idx1');

            // Fields
            $table->string('token_verification', 17);

            $table->tinyInteger('is_evaluated')->default(0);
            $table->tinyInteger('is_reviewed')->default(0);
            $table->tinyInteger('is_active')->default(1);

            $table->text('legal_data');
            $table->text('legal_summary');
            $table->tinyInteger('legal_result')->default(0);
            $table->dateTime('legal_tgl_proses')->nullable();
            $table->string('legal_pic', 50);

            $table->text('finance_data');
            $table->text('finance_summary');
            $table->tinyInteger('finance_result')->default(0);
            $table->dateTime('finance_tgl_proses')->nullable();
            $table->string('finance_pic', 50);

            $table->text('logistik_data');
            $table->text('logistik_summary');
            $table->tinyInteger('logistik_result')->default(0);
            $table->dateTime('logistik_tgl_proses')->nullable();
            $table->string('logistik_pic', 50);

            $table->text('sm_summary');
            $table->tinyInteger('sm_result')->default(0);
            $table->dateTime('sm_tgl_proses')->nullable();
            $table->string('sm_pic', 50);

            $table->text('om_summary');
            $table->tinyInteger('om_result')->default(0);
            $table->dateTime('om_tgl_proses')->nullable();
            $table->string('om_pic', 50);

            $table->text('cfo_summary');
            $table->tinyInteger('cfo_result')->default(0);
            $table->dateTime('cfo_tgl_proses')->nullable();
            $table->string('cfo_pic', 50);

            $table->text('ceo_summary');
            $table->tinyInteger('ceo_result')->default(0);
            $table->dateTime('ceo_tgl_proses')->nullable();
            $table->string('ceo_pic', 50);

            $table->tinyInteger('disposisi_result')->default(0);
            $table->tinyInteger('is_approved')->default(0);
            $table->integer('role_approve')->nullable();
            $table->dateTime('tanggal_approved')->nullable();

            $table->integer('jenis_datanya')->default(0);

            $table->text('finance_data_kyc')->nullable();

            // FK constraint
            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->cascadeOnUpdate(); // no ON DELETE per skema asli
        });
    }

    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropIndex('customer_ver_idx1');
        });

        Schema::dropIfExists('customer_verifications');
    }
};
