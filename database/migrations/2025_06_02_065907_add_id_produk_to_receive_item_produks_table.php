<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdProdukToReceiveItemProduksTable extends Migration
{
    public function up()
    {
        Schema::table('receive_item_produks', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produk')->after('po_produk_id');

            // buat foreign key ke tabel produk
            $table->foreign('id_produk')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('receive_item_produks', function (Blueprint $table) {
            $table->dropForeign(['id_produk']);
            $table->dropColumn('id_produk');
        });
    }
}
