<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->unsignedBigInteger('source_branch_id')->nullable()->after('id_produk');
            $table->unsignedBigInteger('product_price_id')->nullable()->after('source_branch_id');
            $table->foreign('source_branch_id')->references('id_cabang')->on('cabangs')->onDelete('restrict');
            $table->foreign('product_price_id')->references('id')->on('product_prices')->onDelete('restrict');
            $table->index('product_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->dropForeign(['source_branch_id']);
            $table->dropForeign(['product_price_id']);
            $table->dropIndex('penawaran_items_product_price_id_index');
            $table->dropColumn(['source_branch_id', 'product_price_id']);
        });
    }
};
