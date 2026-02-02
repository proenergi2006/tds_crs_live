<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentHargaDasarTable extends Migration
{
    public function up()
    {
        Schema::create('attachment_harga_dasar', function (Blueprint $table) {
            $table->bigIncrements('id_attachment_harga_dasar');
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->text('catatan')->nullable();
            $table->string('lampiran')->nullable(); // simpan nama file/path
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachment_harga_dasar');
    }
}
