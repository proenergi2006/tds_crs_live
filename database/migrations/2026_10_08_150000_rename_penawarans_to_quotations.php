<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_APPROVABLE_TYPE = 'App\Models\Penawaran';

    private const NEW_APPROVABLE_TYPE = 'App\Models\Quotation';

    private const TABLES = [
        'penawarans' => 'quotations',
        'penawaran_items' => 'quotation_items',
    ];

    private const COLUMNS = [
        'quotations' => [
            'id_penawaran' => 'id',
            'id_customer' => 'customer_id',
            'id_cabang' => 'branch_id',
        ],
        'quotation_items' => [
            'id_penawaran_item' => 'id',
            'id_penawaran' => 'quotation_id',
            'id_produk' => 'product_id',
        ],
        'po_customers' => [
            'id_penawaran' => 'quotation_id',
        ],
    ];

    private const SEQUENCES = [
        'penawarans_id_penawaran_seq' => 'quotations_id_seq',
        'penawaran_items_id_penawaran_item_seq' => 'quotation_items_id_seq',
    ];

    private const CONSTRAINTS = [
        'quotations' => [
            'penawarans_biaya_kirim_not_null' => 'quotations_biaya_kirim_not_null',
            'penawarans_brand_check' => 'quotations_brand_check',
            'penawarans_brand_not_null' => 'quotations_brand_not_null',
            'penawarans_customer_contact_id_foreign' => 'quotations_customer_contact_id_foreign',
            'penawarans_discount_not_null' => 'quotations_discount_not_null',
            'penawarans_diskon_not_null' => 'quotations_diskon_not_null',
            'penawarans_disposisi_penawaran_not_null' => 'quotations_disposisi_penawaran_not_null',
            'penawarans_harga_tebus_setelah_diskon_not_null' => 'quotations_harga_tebus_setelah_diskon_not_null',
            'penawarans_id_cabang_foreign' => 'quotations_branch_id_foreign',
            'penawarans_id_cabang_not_null' => 'quotations_branch_id_not_null',
            'penawarans_id_customer_foreign' => 'quotations_customer_id_foreign',
            'penawarans_id_customer_not_null' => 'quotations_customer_id_not_null',
            'penawarans_id_penawaran_not_null' => 'quotations_id_not_null',
            'penawarans_masa_berlaku_not_null' => 'quotations_masa_berlaku_not_null',
            'penawarans_nomor_penawaran_not_null' => 'quotations_nomor_penawaran_not_null',
            'penawarans_nomor_penawaran_unique' => 'quotations_nomor_penawaran_unique',
            'penawarans_oat_not_null' => 'quotations_oat_not_null',
            'penawarans_penawaran_disetujui_not_null' => 'quotations_penawaran_disetujui_not_null',
            'penawarans_pkey' => 'quotations_pkey',
            'penawarans_ppn11_not_null' => 'quotations_ppn11_not_null',
            'penawarans_price_period_id_foreign' => 'quotations_price_period_id_foreign',
            'penawarans_sampai_dengan_not_null' => 'quotations_sampai_dengan_not_null',
            'penawarans_status_check' => 'quotations_status_check',
            'penawarans_status_not_null' => 'quotations_status_not_null',
            'penawarans_subtotal_not_null' => 'quotations_subtotal_not_null',
            'penawarans_token_verifikasi_unique' => 'quotations_token_verifikasi_unique',
            'penawarans_total_not_null' => 'quotations_total_not_null',
            'penawarans_total_with_oat_not_null' => 'quotations_total_with_oat_not_null',
            'penawarans_type_pengiriman_check' => 'quotations_type_pengiriman_check',
            'penawarans_user_id_foreign' => 'quotations_user_id_foreign',
        ],
        'quotation_items' => [
            'penawaran_items_harga_tebus_not_null' => 'quotation_items_harga_tebus_not_null',
            'penawaran_items_id_penawaran_foreign' => 'quotation_items_quotation_id_foreign',
            'penawaran_items_id_penawaran_item_not_null' => 'quotation_items_id_not_null',
            'penawaran_items_id_penawaran_not_null' => 'quotation_items_quotation_id_not_null',
            'penawaran_items_id_produk_foreign' => 'quotation_items_product_id_foreign',
            'penawaran_items_id_produk_not_null' => 'quotation_items_product_id_not_null',
            'penawaran_items_jumlah_harga_not_null' => 'quotation_items_jumlah_harga_not_null',
            'penawaran_items_persen_not_null' => 'quotation_items_persen_not_null',
            'penawaran_items_pkey' => 'quotation_items_pkey',
            'penawaran_items_pnw_produk_source_unique' => 'quotation_items_product_source_unique',
            'penawaran_items_product_price_id_foreign' => 'quotation_items_product_price_id_foreign',
            'penawaran_items_source_branch_id_foreign' => 'quotation_items_source_branch_id_foreign',
            'penawaran_items_volume_order_not_null' => 'quotation_items_volume_order_not_null',
        ],
        'po_customers' => [
            'po_customers_id_penawaran_foreign' => 'po_customers_quotation_id_foreign',
            'po_customers_id_penawaran_not_null' => 'po_customers_quotation_id_not_null',
        ],
    ];

    private const INDEXES = [
        'quotations' => [
            'penawarans_price_period_id_index' => 'quotations_price_period_id_index',
            'penawarans_user_id_index' => 'quotations_user_id_index',
        ],
        'quotation_items' => [
            'penawaran_items_product_price_id_index' => 'quotation_items_product_price_id_index',
        ],
    ];

    public function up(): void
    {
        $this->assertUpPreconditions();

        foreach (self::TABLES as $old => $new) {
            DB::statement("ALTER TABLE {$old} RENAME TO {$new}");
        }

        $this->renameColumns(self::COLUMNS);
        $this->renameSequences(self::SEQUENCES);
        $this->renameConstraints(self::CONSTRAINTS);
        $this->renameIndexes(self::INDEXES);
        $this->ensureQuotationForeignKey();

        DB::statement('ALTER TABLE quotations DROP COLUMN oa_kapal, DROP COLUMN oa_truck');

        DB::table('document_approvals')
            ->where('approvable_type', self::OLD_APPROVABLE_TYPE)
            ->update(['approvable_type' => self::NEW_APPROVABLE_TYPE]);
    }

    public function down(): void
    {
        $this->assertDownPreconditions();

        DB::table('document_approvals')
            ->where('approvable_type', self::NEW_APPROVABLE_TYPE)
            ->update(['approvable_type' => self::OLD_APPROVABLE_TYPE]);

        DB::statement('ALTER TABLE quotations ADD COLUMN oa_kapal numeric(12,2) NULL, ADD COLUMN oa_truck numeric(12,2) NULL');

        $this->renameIndexes($this->flipByTable(self::INDEXES));
        $this->renameConstraints($this->flipByTable(self::CONSTRAINTS));
        $this->renameSequences(array_flip(self::SEQUENCES));
        $this->renameColumns($this->flipByTable(self::COLUMNS));

        foreach (array_flip(self::TABLES) as $current => $original) {
            DB::statement("ALTER TABLE {$current} RENAME TO {$original}");
        }
    }

    private function assertUpPreconditions(): void
    {
        foreach (self::TABLES as $old => $new) {
            if (! $this->tableExists($old)) {
                throw new RuntimeException("Table {$old} does not exist; nothing to rename.");
            }
            if ($this->tableExists($new)) {
                throw new RuntimeException("Table {$new} already exists; refusing to rename {$old} onto it.");
            }
        }

        $oaCount = (int) DB::table('penawarans')
            ->where(fn ($query) => $query->whereNotNull('oa_kapal')->orWhereNotNull('oa_truck'))
            ->count();

        if ($oaCount > 0) {
            throw new RuntimeException("penawarans.oa_kapal/oa_truck holds data in {$oaCount} rows; dropping them cannot be undone. Bring this to Engineer first.");
        }

        $currentTable = array_flip(self::TABLES);

        foreach (self::COLUMNS as $newTable => $map) {
            $table = $currentTable[$newTable] ?? $newTable;
            foreach (array_keys($map) as $column) {
                if (! $this->columnExists($table, $column)) {
                    throw new RuntimeException("Column {$table}.{$column} not found.");
                }
            }
        }

        foreach (array_keys(self::SEQUENCES) as $sequence) {
            if (! $this->relationExists($sequence, 'S')) {
                throw new RuntimeException("Sequence {$sequence} not found.");
            }
        }

    }

    private function assertDownPreconditions(): void
    {
        foreach (self::TABLES as $old => $new) {
            if (! $this->tableExists($new)) {
                throw new RuntimeException("Table {$new} does not exist; nothing to roll back.");
            }
            if ($this->tableExists($old)) {
                throw new RuntimeException("Table {$old} already exists; refusing to rename {$new} onto it.");
            }
        }

        foreach (self::COLUMNS as $table => $map) {
            foreach ($map as $old => $new) {
                if (! $this->columnExists($table, $new)) {
                    throw new RuntimeException("Column {$table}.{$new} not found.");
                }
                if ($this->columnExists($table, $old)) {
                    throw new RuntimeException("Column {$table}.{$old} already exists; refusing to rename {$new} onto it.");
                }
            }
        }

    }

    private function ensureQuotationForeignKey(): void
    {
        $hasForeignKey = DB::selectOne(
            "select 1 as found from pg_constraint c join pg_attribute a on a.attrelid = c.conrelid and a.attnum = any(c.conkey) where c.conrelid = to_regclass('po_customers') and c.contype = 'f' and c.confrelid = to_regclass('quotations') and a.attname = 'quotation_id'"
        ) !== null;

        if ($hasForeignKey) {
            return;
        }

        $orphans = (int) DB::table('po_customers as p')
            ->leftJoin('quotations as q', 'q.id', '=', 'p.quotation_id')
            ->whereNull('q.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException("{$orphans} po_customers rows reference a missing quotation; cannot add the foreign key.");
        }

        DB::statement('ALTER TABLE po_customers ADD CONSTRAINT po_customers_quotation_id_foreign FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE');
    }

    private function renameColumns(array $byTable): void
    {
        foreach ($byTable as $table => $map) {
            foreach ($map as $from => $to) {
                DB::statement("ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}");
            }
        }
    }

    private function renameSequences(array $map): void
    {
        foreach ($map as $from => $to) {
            DB::statement("ALTER SEQUENCE {$from} RENAME TO {$to}");
        }
    }

    private function renameConstraints(array $byTable): void
    {
        foreach ($byTable as $table => $map) {
            foreach ($map as $from => $to) {
                if ($this->constraintExists($table, $from)) {
                    DB::statement("ALTER TABLE {$table} RENAME CONSTRAINT {$from} TO {$to}");
                }
            }
        }
    }

    private function renameIndexes(array $byTable): void
    {
        foreach ($byTable as $table => $map) {
            foreach ($map as $from => $to) {
                if ($this->indexExists($table, $from)) {
                    DB::statement("ALTER INDEX {$from} RENAME TO {$to}");
                }
            }
        }
    }

    private function flipByTable(array $byTable): array
    {
        return array_map(fn (array $map) => array_flip($map), $byTable);
    }

    private function tableExists(string $table): bool
    {
        return $this->relationExists($table, 'r');
    }

    private function relationExists(string $name, string $kind): bool
    {
        return DB::selectOne(
            'select 1 as found from pg_class c join pg_namespace n on n.oid = c.relnamespace where n.nspname = current_schema() and c.relname = ? and c.relkind = ?',
            [$name, $kind]
        ) !== null;
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

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'select 1 as found from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ?',
            [$table, $index]
        ) !== null;
    }
};
