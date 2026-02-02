<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvinsisTable extends Migration
{
    public function up()
    {
        Schema::create('provinsis', function (Blueprint $table) {
            $table->bigIncrements('id_provinsi');
            $table->string('nama_provinsi');
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('provinsis');
    }
}
