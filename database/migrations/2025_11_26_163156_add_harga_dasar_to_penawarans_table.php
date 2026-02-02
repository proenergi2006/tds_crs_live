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
    Schema::table('penawarans', function (Blueprint $table) {
        $table->decimal('harga_dasar', 15, 2)->nullable()->after('oat');
        $table->decimal('ppn_harga_dasar', 15, 2)->nullable()->after('harga_dasar');
        $table->decimal('grand_total_harga_dasar', 15, 2)->nullable()->after('ppn_harga_dasar');
    });
}

public function down(): void
{
    Schema::table('penawarans', function (Blueprint $table) {
        $table->dropColumn(['harga_dasar', 'ppn_harga_dasar', 'grand_total_harga_dasar']);
    });
}
};
