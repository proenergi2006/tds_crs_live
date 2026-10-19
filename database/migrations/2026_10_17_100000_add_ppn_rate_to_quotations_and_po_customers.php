<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quotations', 'po_customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('ppn_rate', 5, 4)->nullable();
            });

            DB::table($tableName)->update(['ppn_rate' => 0.11]);

            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('ppn_rate', 5, 4)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['quotations', 'po_customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('ppn_rate');
            });
        }
    }
};
