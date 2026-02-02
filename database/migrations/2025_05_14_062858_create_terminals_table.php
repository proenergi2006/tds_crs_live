<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalsTable extends Migration
{
    public function up()
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->bigIncrements('id_terminal');
            $table->string('nama_terminal');
            $table->unsignedBigInteger('id_cabang');
            $table->string('kategori_terminal');
            $table->string('inisial')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('telp_terminal')->nullable();
            $table->text('alamat')->nullable();
            $table->string('fax')->nullable();
            $table->string('pic')->nullable();
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            // FK ke tabel cabangs
            $table->foreign('id_cabang')
                  ->references('id_cabang')
                  ->on('cabangs')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('terminals');
    }
}
