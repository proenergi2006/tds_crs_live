<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPosTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_pos', function (Blueprint $table) {
            $table->bigIncrements('id_po');
            $table->unsignedBigInteger('id_vendor');
            $table->unsignedBigInteger('id_produk');
            $table->unsignedBigInteger('id_terminal');
            $table->string('nomor_po');
            $table->date('tanggal_inven');
            $table->decimal('volume_po', 22, 4);
            $table->decimal('harga_tebus', 15, 2);
            $table->string('kd_tax');
            $table->string('terms');
            $table->integer('terms_day');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('ppn11', 15, 2);
            $table->decimal('total_order', 15, 2);
            $table->text('keterangan')->nullable();
            $table->boolean('is_close')->default(0);
            $table->boolean('is_cancel')->default(0);
            $table->text('keterangan_cancel')->nullable();
            $table->date('tanggal_close')->nullable();
            $table->integer('volume_close')->nullable();
            $table->integer('disposisi_po')->default(0);
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            // foreign keys
            $table->foreign('id_vendor')
                  ->references('id_vendor')
                  ->on('vendors')
                  ->onDelete('restrict');
            $table->foreign('id_produk')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
            $table->foreign('id_terminal')
                  ->references('id_terminal')
                  ->on('terminals')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_pos');
    }
}
