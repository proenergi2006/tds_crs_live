<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorsTable extends Migration
{
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->bigIncrements('id_vendor');
            $table->string('nama_vendor');
            $table->string('inisial', 10);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendors');
    }
}
