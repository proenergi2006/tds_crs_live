<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ongkos_kapals', 'catatan')) {
            Schema::table('ongkos_kapals', function (Blueprint $table) {
                $table->text('catatan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ongkos_kapals', 'catatan')) {
            Schema::table('ongkos_kapals', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
