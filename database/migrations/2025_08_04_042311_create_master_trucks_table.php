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
        Schema::create('master_trucks', function (Blueprint $table) {
            $table->id();
            $table->string('nopol')->unique();
            $table->string('jenis_truck');
            $table->float('kapasitas');
            $table->unsignedBigInteger('id_transportir');
            $table->string('dokumen')->nullable();
            $table->date('masa_berlaku')->nullable();
            $table->string('lampiran')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        
            $table->foreign('id_transportir')->references('id')->on('transportirs');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_trucks');
    }
};
