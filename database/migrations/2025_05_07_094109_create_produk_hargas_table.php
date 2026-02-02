<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProdukHargasTable extends Migration
{
    public function up()
    {
        Schema::create('produk_hargas', function (Blueprint $table) {
            $table->bigIncrements('id_produk_harga');
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->unsignedBigInteger('id_cabang');
            $table->unsignedBigInteger('id_produk');
            $table->decimal('harga_price_list', 15, 2);
            $table->decimal('harga_bm', 15, 2);
            $table->text('catatan')->nullable();
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            // foreign keys
            $table->foreign('id_cabang')
                  ->references('id_cabang')
                  ->on('cabangs')
                  ->onDelete('restrict');
            $table->foreign('id_produk')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('produk_hargas');
    }
}
