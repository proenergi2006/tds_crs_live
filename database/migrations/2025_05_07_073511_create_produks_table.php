<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProduksTable extends Migration
{
    public function up()
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->bigIncrements('id_produk');
            $table->string('nama_produk');
            $table->string('merk_dagang')->nullable();
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('id_ukuran');
            $table->integer('stock')->default(0);
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            // kalau mau foreign key ke tabel ukuran:
            $table->foreign('id_ukuran')
                  ->references('id_ukuran')
                  ->on('ukurans')
                  ->onDelete('restrict');

            // jika ingin timestamps Laravel otomatis (opsional):
            // $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produks');
    }
}


