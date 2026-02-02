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
        Schema::create('penawaran_ongkos', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('penawaran_id');
            $table->foreign('penawaran_id')
                ->references('id_penawaran')
                ->on('penawarans')
                ->onDelete('cascade');
        
            $table->unsignedBigInteger('wilayah_id');
            $table->foreign('wilayah_id')
                ->references('id')
                ->on('wilayah_angkuts');
        
            $table->unsignedBigInteger('transportir_id');
            $table->foreign('transportir_id')
                ->references('id')
                ->on('transportirs');
        
            $table->decimal('volume', 12, 2);
            $table->bigInteger('ongkos');
        
            $table->timestamps();
        });
    }        

    public function down(): void
    {
        Schema::dropIfExists('penawaran_ongkos');
    }
};
