<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiveItemProduksTable extends Migration
{
    public function up()
    {
        Schema::create('receive_item_produks', function (Blueprint $table) {
            $table->id(); // id_receive_item_produk
            $table->unsignedBigInteger('receive_item_id'); // FK ke receive_items.id
            $table->unsignedBigInteger('po_produk_id');     // FK ke vendor_pos_produks.id_po_produk
            $table->decimal('harga_tebus', 15, 2);
            $table->decimal('volume_bl', 15, 2);
            $table->decimal('volume_terima', 15, 2);
            $table->timestamps();

            // FK ke receive_items
            $table->foreign('receive_item_id')
                  ->references('id')->on('receive_items')
                  ->onDelete('cascade');

            // FK ke vendor_pos_produks (field id_po_produk)
            $table->foreign('po_produk_id')
                  ->references('id_po_produk')->on('vendor_pos_produks')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('receive_item_produks');
    }
}
