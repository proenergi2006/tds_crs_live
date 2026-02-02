<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuans', function (Blueprint $table) {
            $table->bigIncrements('id_satuan');
            $table->string('nama_satuan', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_time')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamp('lastupdate_time')->nullable();
            $table->string('lastupdate_by', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuans');
    }
};
