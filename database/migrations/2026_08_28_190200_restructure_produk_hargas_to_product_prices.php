<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->unsignedBigInteger('price_period_id')->nullable()->after('id_produk_harga');
        });

        DB::statement('
            UPDATE produk_hargas
            SET price_period_id = price_periods.id
            FROM price_periods
            WHERE produk_hargas.periode_awal = price_periods.start_date
              AND produk_hargas.periode_akhir = price_periods.end_date
        ');

        $orphaned = DB::table('produk_hargas')->whereNull('price_period_id')->count();

        if ($orphaned > 0) {
            throw new \RuntimeException("Migration aborted: {$orphaned} baris produk_hargas gagal di-link ke price_periods.");
        }

        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->unsignedBigInteger('price_period_id')->nullable(false)->change();
            $table->foreign('price_period_id')->references('id')->on('price_periods')->onDelete('restrict');
            $table->index(['price_period_id', 'id_cabang', 'id_produk']);
            $table->dropColumn(['periode_awal', 'periode_akhir', 'margin']);
        });

        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN id_produk_harga TO id');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN id_cabang TO branch_id');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN id_produk TO product_id');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_price_list TO price_list');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_price_list_pe TO price_list_pe');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_bm TO bm_price');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_cogs TO cogs_price');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_margin TO margin_amount');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_om TO om_price');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN harga_ceo TO ceo_price');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN catatan TO notes');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN created_time TO created_at');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN lastupdate_time TO updated_at');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN lastupdate_by TO updated_by');

        Schema::rename('produk_hargas', 'product_prices');
    }

    public function down(): void
    {
        Schema::rename('product_prices', 'produk_hargas');

        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN id TO id_produk_harga');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN branch_id TO id_cabang');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN product_id TO id_produk');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN price_list TO harga_price_list');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN price_list_pe TO harga_price_list_pe');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN bm_price TO harga_bm');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN cogs_price TO harga_cogs');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN margin_amount TO harga_margin');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN om_price TO harga_om');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN ceo_price TO harga_ceo');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN notes TO catatan');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN created_at TO created_time');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN updated_at TO lastupdate_time');
        DB::statement('ALTER TABLE produk_hargas RENAME COLUMN updated_by TO lastupdate_by');

        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->date('periode_awal')->nullable()->after('id_produk_harga');
            $table->date('periode_akhir')->nullable()->after('periode_awal');
            $table->decimal('margin', 12, 2)->default(0)->after('lastupdate_by');
        });

        DB::statement('
            UPDATE produk_hargas
            SET periode_awal = price_periods.start_date, periode_akhir = price_periods.end_date
            FROM price_periods
            WHERE produk_hargas.price_period_id = price_periods.id
        ');

        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->date('periode_awal')->nullable(false)->change();
            $table->date('periode_akhir')->nullable(false)->change();
            $table->dropForeign(['price_period_id']);
            $table->dropIndex('produk_hargas_price_period_id_id_cabang_id_produk_index');
            $table->dropColumn('price_period_id');
        });
    }
};
