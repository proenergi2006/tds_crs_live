<?php

// database/migrations/2025_08_25_000000_add_columns_to_cabangs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCabangsTable extends Migration
{
    public function up(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            $table->string('inisial_cabang')->nullable();
            $table->string('inisial_segel')->nullable();
            $table->text('catatan_cabang')->nullable();
            $table->string('kode_barcode')->nullable();
            $table->integer('urut_ba')->default(0);
            $table->integer('urut_spj')->default(0);
            $table->integer('urut_dn')->default(0);
            $table->integer('urut_dn_kpl')->default(0);
            $table->integer('urut_pr')->default(0);
            $table->integer('urut_po')->default(0);
            $table->integer('urut_po_kpl')->default(0);
            $table->integer('urut_ds')->default(0);
            $table->integer('urut_penawaran')->default(0);
            $table->integer('urut_oslog')->default(0);
            $table->integer('urut_lo')->default(0);
            $table->integer('urut_segel')->default(0);
            $table->integer('stok_segel')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            $table->dropColumn([
                'inisial_cabang', 'inisial_segel', 'catatan_cabang', 'kode_barcode',
                'urut_ba', 'urut_spj', 'urut_dn', 'urut_dn_kpl', 'urut_pr', 'urut_po',
                'urut_po_kpl', 'urut_ds', 'urut_penawaran', 'urut_oslog', 'urut_lo', 'urut_segel', 'stok_segel'
            ]);
        });
    }
}
