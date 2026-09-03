<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('penawaran_items')
            ->select('id_penawaran', 'id_produk', 'source_branch_id')
            ->whereNotNull('source_branch_id')
            ->groupBy('id_penawaran', 'id_produk', 'source_branch_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();

        if ($duplicates > 0) {
            throw new \RuntimeException("Migration aborted: {$duplicates} kombinasi (id_penawaran, id_produk, source_branch_id) duplikat di penawaran_items.");
        }

        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->unique(['id_penawaran', 'id_produk', 'source_branch_id'], 'penawaran_items_pnw_produk_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_items', function (Blueprint $table) {
            $table->dropUnique('penawaran_items_pnw_produk_source_unique');
        });
    }
};
