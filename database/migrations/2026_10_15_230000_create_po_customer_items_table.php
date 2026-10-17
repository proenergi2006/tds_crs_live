<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('po_customer_items')) {
            throw new RuntimeException('Table po_customer_items already exists.');
        }

        Schema::create('po_customer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_customer_id');
            $table->unsignedBigInteger('quotation_item_id');
            $table->decimal('volume', 20, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('transport_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['po_customer_id', 'quotation_item_id']);
            $table->index('quotation_item_id');

            $table->foreign('po_customer_id')->references('id_poc')->on('po_customers')->cascadeOnDelete();
            $table->foreign('quotation_item_id')->references('id')->on('quotation_items');
        });

        DB::statement('ALTER TABLE po_customer_items ADD CONSTRAINT po_customer_items_volume_check CHECK (volume >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('po_customer_items');
    }
};
