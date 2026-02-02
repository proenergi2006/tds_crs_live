<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id('id_cabang');
            $table->string('nama_cabang', 150);
            $table->boolean('is_active')->default(true);
            // kalau ingin pakai created_time manual seperti Role:
            $table->timestamp('created_time');
            $table->string('created_by')->nullable();
            $table->timestamp('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};
