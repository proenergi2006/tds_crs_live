<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kontak tujuan pindah ke relasi customer_contact; kepada/alamat kini diturunkan live dari customer.
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['kepada', 'jabatan', 'telepon', 'nama', 'alamat']);
        });

        Schema::table('penawarans_proenergi', function (Blueprint $table) {
            $table->dropColumn(['kepada', 'jabatan', 'telepon', 'nama', 'alamat']);
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->string('kepada')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('telepon')->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
        });

        Schema::table('penawarans_proenergi', function (Blueprint $table) {
            $table->string('kepada')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('telepon')->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
        });
    }
};
