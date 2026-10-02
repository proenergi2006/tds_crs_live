<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penawaran_oas')) {
            $count = DB::table('penawaran_oas')->count();

            if ($count > 0) {
                throw new RuntimeException("penawaran_oas is not empty ({$count} rows) — bring this to Engineer before dropping.");
            }

            Schema::drop('penawaran_oas');
        }
    }

    public function down(): void {}
};
