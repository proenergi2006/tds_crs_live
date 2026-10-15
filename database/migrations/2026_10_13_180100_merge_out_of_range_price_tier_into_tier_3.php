<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = ['quotations', 'quotation_items'];

    private const TIERS = ['tier_1', 'tier_2', 'tier_3'];

    private const LEGACY_TIERS = ['tier_1', 'tier_2', 'tier_3', 'out_of_range'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->where('price_tier', 'out_of_range')->update(['price_tier' => 'tier_3']);
        }

        foreach (self::TABLES as $table) {
            $this->replaceCheck($table, self::TIERS);
        }
    }

    public function down(): void
    {
        // sengaja gak balikin data: hasil backfill gak bisa dibedain dari Tier III asli
        foreach (self::TABLES as $table) {
            $this->replaceCheck($table, self::LEGACY_TIERS);
        }
    }

    private function replaceCheck(string $table, array $tiers): void
    {
        $allowed = "'" . implode("','", $tiers) . "'";

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_price_tier_check");
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_price_tier_check CHECK (price_tier IN ({$allowed}))");
    }
};
