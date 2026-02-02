<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenawaranItemsTable extends Migration
{
    public function up()
    {
        Schema::create('penawaran_items', function (Blueprint $table) {
            $table->bigIncrements('id_penawaran_item');

            // FK ke tabel penawarans
            $table->unsignedBigInteger('id_penawaran');
            // FK ke tabel produks
            $table->unsignedBigInteger('id_produk');

            $table->decimal('volume_order', 20, 2)->default(0);
            $table->decimal('harga_tebus',   20, 2)->default(0);
            $table->decimal('jumlah_harga',  30, 2)->default(0);

            // Audit sederhana
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('id_penawaran')
                  ->references('id_penawaran')->on('penawarans')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_produk')
                  ->references('id_produk')->on('produks')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('penawaran_items');
    }
}
