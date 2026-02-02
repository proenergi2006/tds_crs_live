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
        Schema::create('ongkos_truck_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ongkos_truck');
            $table->unsignedBigInteger('id_volume');
            $table->decimal('oa', 15, 2);
            $table->timestamps();
        
            $table->foreign('id_ongkos_truck')->references('id')->on('ongkos_trucks')->onDelete('cascade');
            $table->foreign('id_volume')->references('id_volume')->on('volumes')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ongkos_truck_details');
    }
};
