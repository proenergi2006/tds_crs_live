<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ukurans', function (Blueprint $table) {
            $table->dropColumn(['nilai', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::table('ukurans', function (Blueprint $table) {
            $table->decimal('nilai', 10, 4)->nullable(); // sesuaikan tipe datanya
            $table->string('jenis')->nullable();         // sesuaikan tipe datanya
        });
    }
};

