<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['transport_price', 'transport_snapshot'] as $column) {
            if ($this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} already exists.");
            }
        }

        DB::statement('ALTER TABLE quotation_items ADD COLUMN transport_price numeric(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotation_items ADD COLUMN transport_snapshot jsonb NULL');

        DB::statement('UPDATE quotation_items qi SET transport_price = COALESCE(q.oat, 0) FROM quotations q WHERE q.id = qi.quotation_id');
    }

    public function down(): void
    {
        foreach (['transport_price', 'transport_snapshot'] as $column) {
            if (! $this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} not found.");
            }
        }

        DB::statement('ALTER TABLE quotation_items DROP COLUMN transport_snapshot');
        DB::statement('ALTER TABLE quotation_items DROP COLUMN transport_price');
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
