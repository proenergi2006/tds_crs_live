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
        Schema::create('ongkos_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ongkos_kapal');
            $table->unsignedBigInteger('id_volume');
            $table->decimal('oa', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        
            $table->foreign('id_ongkos_kapal')->references('id')->on('ongkos_kapals')->onDelete('cascade');
            $table->foreign('id_volume')->references('id_volume')->on('volumes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ongkos_details');
    }
};
