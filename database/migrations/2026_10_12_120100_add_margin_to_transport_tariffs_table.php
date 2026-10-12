<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->columnExists('transport_tariffs', 'margin')) {
            throw new RuntimeException('Column transport_tariffs.margin already exists.');
        }

        DB::statement('ALTER TABLE transport_tariffs ADD COLUMN margin numeric(15,2) NULL');
    }

    public function down(): void
    {
        if (! $this->columnExists('transport_tariffs', 'margin')) {
            throw new RuntimeException('Column transport_tariffs.margin not found.');
        }

        DB::statement('ALTER TABLE transport_tariffs DROP COLUMN margin');
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
