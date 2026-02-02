<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            // relasi ke detail PO (vendor_pos_produks.id_po_produk)
            $table->unsignedBigInteger('po_produk_id');
            // relasi ke header receive_items.id
            $table->unsignedBigInteger('receive_item_id');
            // relasi ke produk (produks.id_produk)
            $table->unsignedBigInteger('produk_id');
            // volume masuk ke stock
            $table->decimal('volume', 22, 4);
            // harga tebus per‐unit
            $table->decimal('harga_tebus', 22, 4);
            $table->timestamps();

            $table->foreign('po_produk_id')
                  ->references('id_po_produk')
                  ->on('vendor_pos_produks')
                  ->onDelete('cascade');

            $table->foreign('receive_item_id')
                  ->references('id')
                  ->on('receive_items')
                  ->onDelete('cascade');

            $table->foreign('produk_id')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
}
