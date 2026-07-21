<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('id', 2)->primary();
            $table->string('name');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
