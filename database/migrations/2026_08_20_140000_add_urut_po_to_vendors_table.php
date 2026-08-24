<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vendor model pakai urut_po (auto-numbering PO Supplier per vendor) tapi kolomnya gak pernah ada migration.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendors', 'urut_po')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->integer('urut_po')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'urut_po')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('urut_po');
            });
        }
    }
};
