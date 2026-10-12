<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TIERS = ['tier_1', 'tier_2', 'tier_3', 'out_of_range'];

    private const TABLES = ['quotations', 'quotation_items'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if ($this->columnExists($table, 'price_tier')) {
                throw new RuntimeException("Column {$table}.price_tier already exists.");
            }
        }

        $allowed = "'" . implode("','", self::TIERS) . "'";

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN price_tier varchar(20) NULL");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_price_tier_check CHECK (price_tier IN ({$allowed}))");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! $this->columnExists($table, 'price_tier')) {
                throw new RuntimeException("Column {$table}.price_tier not found.");
            }
        }

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_price_tier_check");
            DB::statement("ALTER TABLE {$table} DROP COLUMN price_tier");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
