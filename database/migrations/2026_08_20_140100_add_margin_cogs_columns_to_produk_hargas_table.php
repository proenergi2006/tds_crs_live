<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ProdukHarga model pakai kolom-kolom ini (margin/COGS basis work) tapi gak pernah ada migration -- ditambah manual dulu di DB.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            if (!Schema::hasColumn('produk_hargas', 'margin')) {
                $table->decimal('margin', 12, 2)->default(0)->after('lastupdate_by');
            }
            if (!Schema::hasColumn('produk_hargas', 'harga_cogs')) {
                $table->decimal('harga_cogs', 18, 2)->nullable()->after('margin');
            }
            if (!Schema::hasColumn('produk_hargas', 'harga_margin')) {
                $table->decimal('harga_margin', 18, 2)->nullable()->after('harga_cogs');
            }
            if (!Schema::hasColumn('produk_hargas', 'harga_om')) {
                $table->decimal('harga_om', 18, 2)->nullable()->after('harga_margin');
            }
            if (!Schema::hasColumn('produk_hargas', 'harga_ceo')) {
                $table->decimal('harga_ceo', 15, 2)->nullable()->after('harga_om');
            }
            if (!Schema::hasColumn('produk_hargas', 'harga_price_list_pe')) {
                $table->decimal('harga_price_list_pe', 15, 2)->default(0)->after('harga_ceo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            foreach (['margin', 'harga_cogs', 'harga_margin', 'harga_om', 'harga_ceo', 'harga_price_list_pe'] as $column) {
                if (Schema::hasColumn('produk_hargas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
