<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regencies', function (Blueprint $table) {
            $table->char('id', 5)->primary();
            $table->char('province_id', 2);
            $table->string('name');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regencies');
    }
};
