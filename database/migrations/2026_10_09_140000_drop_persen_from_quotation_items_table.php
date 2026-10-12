<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->columnExists('quotation_items', 'persen')) {
            throw new RuntimeException('Column quotation_items.persen not found.');
        }

        DB::statement('ALTER TABLE quotation_items DROP COLUMN persen');
    }

    public function down(): void
    {
        if ($this->columnExists('quotation_items', 'persen')) {
            throw new RuntimeException('Column quotation_items.persen already exists.');
        }

        DB::statement('ALTER TABLE quotation_items ADD COLUMN persen numeric(5,2) NOT NULL DEFAULT 0');
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
