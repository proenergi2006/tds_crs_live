<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->char('id', 8)->primary();
            $table->char('regency_id', 5);
            $table->char('province_id', 2);
            $table->string('name');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
