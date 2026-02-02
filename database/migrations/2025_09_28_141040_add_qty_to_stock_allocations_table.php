<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            // pakai decimal biar fleksibel (liter dsb)
            if (!Schema::hasColumn('stock_allocations', 'qty')) {
                $table->decimal('qty', 22, 4)->default(0)->after('id_prd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('stock_allocations', 'qty')) {
                $table->dropColumn('qty');
            }
        });
    }
};
