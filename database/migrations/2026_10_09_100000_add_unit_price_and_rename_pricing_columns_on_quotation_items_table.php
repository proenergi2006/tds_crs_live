<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNS = [
        'volume_order' => 'quantity',
        'jumlah_harga' => 'line_total',
    ];

    private const CONSTRAINTS = [
        'quotation_items_volume_order_not_null' => 'quotation_items_quantity_not_null',
        'quotation_items_jumlah_harga_not_null' => 'quotation_items_line_total_not_null',
    ];

    public function up(): void
    {
        $this->assertUpPreconditions();

        DB::statement('ALTER TABLE quotation_items ADD COLUMN unit_price numeric(15,2) NULL');
        DB::statement('UPDATE quotation_items qi SET unit_price = q.harga_dasar FROM quotations q WHERE q.id = qi.quotation_id');
        DB::statement('ALTER TABLE quotation_items ALTER COLUMN unit_price SET NOT NULL');

        foreach (self::COLUMNS as $from => $to) {
            DB::statement("ALTER TABLE quotation_items RENAME COLUMN {$from} TO {$to}");
        }

        foreach (self::CONSTRAINTS as $from => $to) {
            $this->renameConstraintIfExists($from, $to);
        }

        DB::statement('UPDATE quotation_items SET line_total = ROUND(unit_price * quantity, 2)');
    }

    public function down(): void
    {
        $this->assertDownPreconditions();

        DB::statement('UPDATE quotation_items SET line_total = harga_tebus * quantity');

        foreach (array_flip(self::CONSTRAINTS) as $current => $original) {
            $this->renameConstraintIfExists($current, $original);
        }

        foreach (array_flip(self::COLUMNS) as $current => $original) {
            DB::statement("ALTER TABLE quotation_items RENAME COLUMN {$current} TO {$original}");
        }

        DB::statement('ALTER TABLE quotation_items DROP COLUMN unit_price');
    }

    private function assertUpPreconditions(): void
    {
        foreach (['unit_price', 'quantity', 'line_total'] as $column) {
            if ($this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} already exists.");
            }
        }

        foreach (array_keys(self::COLUMNS) as $column) {
            if (! $this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} not found.");
            }
        }

        foreach (self::CONSTRAINTS as $from => $to) {
            if ($this->constraintExists('quotation_items', $from) && $this->constraintExists('quotation_items', $to)) {
                throw new RuntimeException("Constraint {$to} on quotation_items already exists.");
            }
        }

        $withoutPrice = (int) DB::table('quotation_items as qi')
            ->join('quotations as q', 'q.id', '=', 'qi.quotation_id')
            ->whereNull('q.harga_dasar')
            ->count();

        if ($withoutPrice > 0) {
            throw new RuntimeException("{$withoutPrice} quotation_items belong to quotations with NULL harga_dasar; unit_price cannot be backfilled as NOT NULL.");
        }
    }

    private function assertDownPreconditions(): void
    {
        foreach (['unit_price', 'quantity', 'line_total'] as $column) {
            if (! $this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} not found.");
            }
        }

        foreach (array_keys(self::COLUMNS) as $column) {
            if ($this->columnExists('quotation_items', $column)) {
                throw new RuntimeException("Column quotation_items.{$column} already exists; refusing to rename onto it.");
            }
        }

    }

    private function renameConstraintIfExists(string $from, string $to): void
    {
        if ($this->constraintExists('quotation_items', $from)) {
            DB::statement("ALTER TABLE quotation_items RENAME CONSTRAINT {$from} TO {$to}");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return DB::selectOne(
            'select 1 as found from pg_constraint where conrelid = to_regclass(?) and conname = ?',
            [$table, $constraint]
        ) !== null;
    }
};
