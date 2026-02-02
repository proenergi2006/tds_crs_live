<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('wilayah_angkuts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('id_provinsi');
        $table->unsignedBigInteger('id_kabupaten');
        $table->string('destinasi');
        $table->timestamps();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();

        $table->foreign('id_provinsi')->references('id_provinsi')->on('provinsis')->onDelete('cascade');
        $table->foreign('id_kabupaten')->references('id_kabupaten')->on('kabupatens')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wilayah_angkuts');
    }
};
