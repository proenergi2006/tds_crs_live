<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPosProduksTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_pos_produks', function (Blueprint $table) {
            $table->bigIncrements('id_po_produk');
            $table->unsignedBigInteger('id_po');
            $table->unsignedBigInteger('id_produk');
            $table->decimal('volume_po', 22, 4);
            $table->decimal('harga_tebus', 22, 4);
            $table->decimal('jumlah_harga', 22, 4);
            $table->dateTime('created_time')->nullable();
            $table->dateTime('lastupdate_time')->nullable();

            $table->foreign('id_po')
                  ->references('id_po')
                  ->on('vendor_pos')
                  ->onDelete('cascade');
            $table->foreign('id_produk')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_pos_produks');
    }
}
