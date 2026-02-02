<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ongkos_kapals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transportir');
            $table->unsignedBigInteger('id_angkut_wilayah');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        
            $table->foreign('id_transportir')->references('id')->on('transportirs');
            $table->foreign('id_angkut_wilayah')->references('id')->on('wilayah_angkuts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ongkos_kapals');
    }
};
