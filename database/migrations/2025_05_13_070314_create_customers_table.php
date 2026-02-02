<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id_customer');
            $table->unsignedBigInteger('id_user');
            $table->string('email')->unique();
            $table->unsignedBigInteger('id_provinsi');
            $table->unsignedBigInteger('id_kabupaten');
            $table->string('postal_code')->nullable();
            $table->string('telepon')->nullable();
            $table->string('jenis_customer')->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->text('alamat_perusahaan')->nullable();
            $table->string('fax')->nullable();
            $table->dateTime('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();

            // foreign keys
            $table->foreign('id_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('id_provinsi')
                  ->references('id_provinsi')->on('provinsis')
                  ->onDelete('restrict');
            $table->foreign('id_kabupaten')
                  ->references('id_kabupaten')->on('kabupatens')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
