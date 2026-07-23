<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_contacts_legacy');
    }

    public function down(): void
    {
        Schema::create('customer_contacts_legacy', function (Blueprint $table) {
            $table->integer('id_customer')->primary();

            $table->string('pic_decision_name', 300)->nullable();
            $table->string('pic_decision_position', 300)->nullable();
            $table->string('pic_decision_telp', 20);
            $table->string('pic_decision_mobile', 20);
            $table->string('pic_decision_email', 300)->nullable();

            $table->string('pic_ordering_name', 300)->nullable();
            $table->string('pic_ordering_position', 300)->nullable();
            $table->string('pic_ordering_telp', 20);
            $table->string('pic_ordering_mobile', 20);
            $table->string('pic_ordering_email', 300)->nullable();

            $table->string('pic_billing_name', 300)->nullable();
            $table->string('pic_billing_position', 300)->nullable();
            $table->string('pic_billing_telp', 20);
            $table->string('pic_billing_mobile', 20);
            $table->string('pic_billing_email', 300)->nullable();

            $table->string('pic_invoice_name', 300)->nullable();
            $table->string('pic_invoice_position', 300)->nullable();
            $table->string('pic_invoice_telp', 20);
            $table->string('pic_invoice_mobile', 20);
            $table->string('pic_invoice_email', 300)->nullable();

            $table->text('product_delivery_address')->nullable();
            $table->string('invoice_delivery_addr_primary', 300)->nullable();
            $table->string('invoice_delivery_addr_secondary', 300)->nullable();

            $table->string('pic_fuelman_name', 300)->nullable();
            $table->string('pic_fuelman_position', 300)->nullable();
            $table->string('pic_fuelman_telp', 50)->nullable();
            $table->string('pic_fuelman_mobile', 20)->nullable();
            $table->string('pic_fuelman_email', 300)->nullable();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
