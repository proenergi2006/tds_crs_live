<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            // nullable & tanpa default — data lama sengaja tidak di-backfill, wajib diisi cuma untuk entry baru (divalidasi di Backend)
            $table->string('cogs_basis')->nullable()->after('harga_cogs');
        });
    }

    public function down(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->dropColumn('cogs_basis');
        });
    }
};
