<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('kabupatens', function (Blueprint $table) {
            $table->bigIncrements('id_kabupaten');
            $table->unsignedBigInteger('id_provinsi');
            $table->string('nama_kabupaten');
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            $table->foreign('id_provinsi')
                  ->references('id_provinsi')
                  ->on('provinsis')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kabupatens');
    }
};
