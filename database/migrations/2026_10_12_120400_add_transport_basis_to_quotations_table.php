<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->columnExists('quotations', 'transport_basis')) {
            throw new RuntimeException('Column quotations.transport_basis already exists.');
        }

        DB::statement('ALTER TABLE quotations ADD COLUMN transport_basis varchar(10) NULL');
        DB::statement("ALTER TABLE quotations ADD CONSTRAINT quotations_transport_basis_check CHECK (transport_basis IN ('loco','franco'))");

        DB::statement(<<<'SQL'
            UPDATE quotations q SET transport_basis = s.basis
            FROM (
                SELECT qi.quotation_id,
                       CASE
                           WHEN count(*) = count(*) FILTER (WHERE pp.cogs_basis = 'loco') THEN 'loco'
                           WHEN count(*) = count(*) FILTER (WHERE pp.cogs_basis = 'franco') THEN 'franco'
                       END AS basis
                FROM quotation_items qi
                LEFT JOIN product_prices pp ON pp.id = qi.product_price_id
                GROUP BY qi.quotation_id
            ) s
            WHERE s.quotation_id = q.id AND s.basis IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        if (! $this->columnExists('quotations', 'transport_basis')) {
            throw new RuntimeException('Column quotations.transport_basis not found.');
        }

        DB::statement('ALTER TABLE quotations DROP CONSTRAINT quotations_transport_basis_check');
        DB::statement('ALTER TABLE quotations DROP COLUMN transport_basis');
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
