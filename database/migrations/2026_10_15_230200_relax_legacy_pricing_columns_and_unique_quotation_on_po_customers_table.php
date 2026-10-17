<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoDuplicateQuotations();

        DB::statement('ALTER TABLE po_customers ALTER COLUMN harga_poc DROP NOT NULL');
        DB::statement('ALTER TABLE po_customers ALTER COLUMN produk_poc DROP NOT NULL');
        DB::statement('ALTER TABLE po_customers ALTER COLUMN volume_poc TYPE numeric(20,2) USING volume_poc::numeric(20,2)');
        DB::statement('ALTER TABLE po_customers ALTER COLUMN volume_poc DROP NOT NULL');

        $this->dropLegacyNotNullChecks();

        DB::statement('DROP INDEX IF EXISTS "IDX_50F6A22FB4EA4E60"');

        Schema::table('po_customers', function (Blueprint $table) {
            $table->unique('quotation_id');
        });
    }

    public function down(): void
    {
        $this->assertLegacyColumnsRestorable();

        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropUnique(['quotation_id']);
        });

        DB::statement('ALTER TABLE po_customers ALTER COLUMN volume_poc TYPE integer USING volume_poc::integer');

        DB::statement('ALTER TABLE po_customers ALTER COLUMN harga_poc SET NOT NULL');
        DB::statement('ALTER TABLE po_customers ALTER COLUMN produk_poc SET NOT NULL');
        DB::statement('ALTER TABLE po_customers ALTER COLUMN volume_poc SET NOT NULL');
    }

    private function assertNoDuplicateQuotations(): void
    {
        $duplicates = DB::select(
            'SELECT quotation_id, string_agg(id_poc::text, \', \' ORDER BY id_poc) AS id_pocs '
            . 'FROM po_customers GROUP BY quotation_id HAVING count(*) > 1 ORDER BY quotation_id'
        );

        if ($duplicates === []) {
            return;
        }

        $lines = array_map(
            fn ($row) => "quotation {$row->quotation_id}: id_poc {$row->id_pocs}",
            $duplicates
        );

        throw new RuntimeException('Cannot add unique quotation_id; duplicate PO found: ' . implode('; ', $lines));
    }

    private function assertLegacyColumnsRestorable(): void
    {
        $missing = DB::selectOne(
            'SELECT count(*) AS total, min(id_poc) AS sample FROM po_customers '
            . 'WHERE harga_poc IS NULL OR produk_poc IS NULL OR volume_poc IS NULL'
        );

        if ((int) $missing->total > 0) {
            throw new RuntimeException(
                "Cannot restore NOT NULL on legacy columns; {$missing->total} PO have NULL values (e.g. id_poc {$missing->sample})."
            );
        }

        $fractional = DB::selectOne(
            'SELECT count(*) AS total, min(id_poc) AS sample FROM po_customers WHERE volume_poc <> trunc(volume_poc)'
        );

        if ((int) $fractional->total > 0) {
            throw new RuntimeException(
                "Cannot convert volume_poc back to integer; {$fractional->total} PO have fractional volume (e.g. id_poc {$fractional->sample})."
            );
        }
    }

    private function dropLegacyNotNullChecks(): void
    {
        DB::statement('ALTER TABLE po_customers DROP CONSTRAINT IF EXISTS po_customers_harga_poc_not_null');
        DB::statement('ALTER TABLE po_customers DROP CONSTRAINT IF EXISTS po_customers_produk_poc_not_null');
        DB::statement('ALTER TABLE po_customers DROP CONSTRAINT IF EXISTS po_customers_volume_poc_not_null');
    }
};
