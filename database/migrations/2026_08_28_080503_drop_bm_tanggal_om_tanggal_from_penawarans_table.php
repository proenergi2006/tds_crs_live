<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            if (Schema::hasColumn('penawarans', 'bm_tanggal')) {
                $table->dropColumn('bm_tanggal');
            }
            if (Schema::hasColumn('penawarans', 'om_tanggal')) {
                $table->dropColumn('om_tanggal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->timestamp('bm_tanggal')->nullable();
            $table->timestamp('om_tanggal')->nullable();
        });
    }
};
