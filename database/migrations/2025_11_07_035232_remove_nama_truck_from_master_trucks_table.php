<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('master_trucks', function (Blueprint $table) {
            $table->dropColumn('nama_truck');
        });
    }

    public function down(): void
    {
        Schema::table('master_trucks', function (Blueprint $table) {
            $table->string('nama_truck', 100)->nullable();
        });
    }
};
