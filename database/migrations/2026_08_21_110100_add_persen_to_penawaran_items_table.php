<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sama seperti type_pengiriman di penawarans -- kolom ada di live, gak pernah
        // tercatat di migration manapun buat varian TDS (cuma ada di migration Proenergi)
        if (!Schema::hasColumn('penawaran_items', 'persen')) {
            Schema::table('penawaran_items', function (Blueprint $table) {
                $table->decimal('persen', 5, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penawaran_items', 'persen')) {
            Schema::table('penawaran_items', function (Blueprint $table) {
                $table->dropColumn('persen');
            });
        }
    }
};
