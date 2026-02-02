<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->decimal('biaya_kirim', 15, 2)->default(0)->after('other_cost');
                $table->decimal('diskon', 5, 2)->default(0)->after('biaya_kirim'); // % diskon   // Rp / volume
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['biaya_kirim', 'diskon']);
        });
    }
};
