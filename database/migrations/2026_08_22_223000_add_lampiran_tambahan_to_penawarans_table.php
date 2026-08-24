<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('penawarans', 'lampiran_tambahan')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->text('lampiran_tambahan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penawarans', 'lampiran_tambahan')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->dropColumn('lampiran_tambahan');
            });
        }
    }
};
