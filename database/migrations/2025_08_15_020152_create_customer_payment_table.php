<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payment', function (Blueprint $table) {
            // match legacy options
            // $table->engine = 'InnoDB';
            // $table->charset = 'latin1';
            // $table->collation = 'latin1_swedish_ci';

            // PK (bukan auto-increment)
            $table->integer('id_customer')->primary();

            $table->string('email_billing', 300)->nullable();
            $table->string('alamat_billing', 300)->nullable();

            $table->integer('prov_billing')->nullable();
            $table->integer('kab_billing')->nullable();

            $table->string('postalcode_billing', 50)->nullable();

            $table->string('telp_billing', 20); // NOT NULL
            $table->string('fax_billing', 20);  // NOT NULL

            $table->tinyInteger('payment_schedule'); // NOT NULL
            $table->string('payment_schedule_other', 300)->nullable();

            $table->tinyInteger('payment_method'); // NOT NULL
            $table->string('payment_method_other', 300)->nullable();

            $table->tinyInteger('invoice'); // NOT NULL
            $table->text('ket_extra');      // NOT NULL

            $table->string('kecamatan_billing', 300)->nullable();
            $table->string('kelurahan_billing', 300)->nullable();

            $table->string('calculate_method', 300)->nullable();

            $table->string('bank_name', 300)->nullable();
            $table->string('curency', 300)->nullable(); // ejaan mengikuti source
            $table->string('bank_address', 300)->nullable();
            $table->string('account_number', 300)->nullable();

            // source pakai INT(2): di MySQL tetap 4 byte; kalau mau, bisa ganti ke tinyInteger/smallInteger
            $table->integer('credit_facility')->nullable();
            $table->string('creditor', 300)->nullable();

            // Index sesuai nama legacy
            $table->index('prov_billing', 'pro_cust_payment_idx1');
            $table->index('kab_billing', 'pro_cust_payment_idx2');

            // Foreign keys
            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('prov_billing')
                ->references('id_provinsi')->on('provinsis')
                ->cascadeOnUpdate();

            $table->foreign('kab_billing')
                ->references('id_kabupaten')->on('kabupatens')
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment');
    }
};
