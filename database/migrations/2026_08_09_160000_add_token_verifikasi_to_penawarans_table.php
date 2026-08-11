<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            // nullable, baru terisi pas OM approve -- unique index cukup jadi safety net, gak perlu retry-loop
            $table->string('token_verifikasi')->nullable();

            $table->unique('token_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropUnique(['token_verifikasi']);
            $table->dropColumn('token_verifikasi');
        });
    }
};
