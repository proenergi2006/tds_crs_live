<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('volumes', function (Blueprint $table) {
            $table->id('id_volume');
            $table->decimal('volume', 15, 2)->default(0); // Bisa disesuaikan precision jika perlu
            $table->unsignedBigInteger('id_satuan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key ke satuan
            $table->foreign('id_satuan')->references('id_satuan')->on('satuans')->onDelete('restrict');

            // (optional) tambahkan jika ada users table
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volumes');
    }
};

