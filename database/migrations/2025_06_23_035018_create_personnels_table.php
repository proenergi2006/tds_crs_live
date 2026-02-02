<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transportir');
            $table->string('nama');
            $table->string('photo')->nullable();
            $table->string('nama_dokumen')->nullable();
            $table->date('masa_berlaku')->nullable();
            $table->string('lampiran')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('id_transportir')->references('id')->on('transportirs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};

