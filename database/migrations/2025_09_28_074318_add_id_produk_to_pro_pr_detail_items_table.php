<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pro_pr_detail_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pro_pr_detail_items', 'id_produk')) {
                $table->unsignedBigInteger('id_produk')->nullable()->after('id_penawaran_item');
                $table->index('id_produk', 'pro_prdi_idx_produk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pro_pr_detail_items', function (Blueprint $table) {
            if (Schema::hasColumn('pro_pr_detail_items', 'id_produk')) {
                $table->dropIndex('pro_prdi_idx_produk');
                $table->dropColumn('id_produk');
            }
        });
    }
};
