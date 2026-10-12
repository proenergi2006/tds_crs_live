<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const LEGACY_TYPES = ['KAPAL', 'TRUCK'];

    private const NOT_NULL_CONSTRAINTS = [
        'penawaran_ongkos_id_not_null' => 'quotation_transports_id_not_null',
        'penawaran_ongkos_penawaran_id_not_null' => 'quotation_transports_quotation_id_not_null',
    ];

    public function up(): void
    {
        $this->assertUpPreconditions();

        DB::statement('ALTER TABLE penawaran_ongkos RENAME TO quotation_transports');
        DB::statement('ALTER SEQUENCE penawaran_ongkos_id_seq RENAME TO quotation_transports_id_seq');
        $this->renameConstraintIfExists('quotation_transports', 'penawaran_ongkos_pkey', 'quotation_transports_pkey');

        DB::statement('ALTER TABLE quotation_transports RENAME COLUMN penawaran_id TO quotation_id');
        DB::statement('ALTER TABLE quotation_transports RENAME COLUMN jenis TO transport_type');
        $this->renameConstraintIfExists('quotation_transports', 'penawaran_ongkos_penawaran_id_foreign', 'quotation_transports_quotation_id_foreign');
        $this->ensureQuotationForeignKey();
        $this->renameIndexIfExists('quotation_transports', 'penawaran_ongkos_jenis_index', 'quotation_transports_transport_type_index');

        foreach (self::NOT_NULL_CONSTRAINTS as $old => $new) {
            $this->renameConstraintIfExists('quotation_transports', $old, $new);
        }

        DB::statement("UPDATE quotation_transports SET transport_type = 'VESSEL' WHERE transport_type = 'KAPAL'");

        DB::statement('ALTER TABLE quotation_transports ADD COLUMN transport_tariff_id bigint NULL');

        $unmatched = [];
        foreach (DB::table('quotation_transports')->orderBy('id')->get() as $leg) {
            $tariffId = $this->resolveTariffId($leg);

            if ($tariffId === null) {
                $unmatched[] = $leg;

                continue;
            }

            DB::table('quotation_transports')->where('id', $leg->id)->update(['transport_tariff_id' => $tariffId]);
        }

        $this->reportUnmatched($unmatched);

        if ($unmatched !== []) {
            DB::table('quotation_transports')->whereIn('id', array_map(fn ($leg) => $leg->id, $unmatched))->delete();
        }

        $this->assertNoDuplicateLegs();

        DB::statement('ALTER TABLE quotation_transports ALTER COLUMN transport_tariff_id SET NOT NULL');
        DB::statement('ALTER TABLE quotation_transports ALTER COLUMN transport_type SET NOT NULL');
        DB::statement('ALTER TABLE quotation_transports ADD CONSTRAINT quotation_transports_transport_tariff_id_foreign FOREIGN KEY (transport_tariff_id) REFERENCES transport_tariffs(id) ON DELETE RESTRICT');
        DB::statement("ALTER TABLE quotation_transports ADD CONSTRAINT quotation_transports_transport_type_check CHECK (transport_type IN ('VESSEL','TRUCK'))");
        DB::statement('ALTER TABLE quotation_transports ADD CONSTRAINT quotation_transports_quotation_id_transport_type_unique UNIQUE (quotation_id, transport_type)');

        DB::statement('ALTER TABLE quotation_transports DROP COLUMN wilayah_id, DROP COLUMN transportir_id, DROP COLUMN volume_id, DROP COLUMN ongkos');
    }

    public function down(): void
    {
        $this->assertDownPreconditions();

        DB::statement('ALTER TABLE quotation_transports DROP CONSTRAINT quotation_transports_quotation_id_transport_type_unique');
        DB::statement('ALTER TABLE quotation_transports DROP CONSTRAINT quotation_transports_transport_type_check');
        DB::statement('ALTER TABLE quotation_transports DROP CONSTRAINT quotation_transports_transport_tariff_id_foreign');

        DB::statement('ALTER TABLE quotation_transports RENAME TO penawaran_ongkos');
        DB::statement('ALTER SEQUENCE quotation_transports_id_seq RENAME TO penawaran_ongkos_id_seq');
        $this->renameConstraintIfExists('penawaran_ongkos', 'quotation_transports_pkey', 'penawaran_ongkos_pkey');

        foreach (array_flip(self::NOT_NULL_CONSTRAINTS) as $current => $original) {
            $this->renameConstraintIfExists('penawaran_ongkos', $current, $original);
        }

        $this->renameConstraintIfExists('penawaran_ongkos', 'quotation_transports_quotation_id_foreign', 'penawaran_ongkos_penawaran_id_foreign');
        $this->renameIndexIfExists('penawaran_ongkos', 'quotation_transports_transport_type_index', 'penawaran_ongkos_jenis_index');
        DB::statement('ALTER TABLE penawaran_ongkos RENAME COLUMN quotation_id TO penawaran_id');
        DB::statement('ALTER TABLE penawaran_ongkos RENAME COLUMN transport_type TO jenis');

        DB::statement('ALTER TABLE penawaran_ongkos ALTER COLUMN jenis DROP NOT NULL');
        DB::statement("UPDATE penawaran_ongkos SET jenis = 'KAPAL' WHERE jenis = 'VESSEL'");

        DB::statement('ALTER TABLE penawaran_ongkos ADD COLUMN wilayah_id bigint NULL, ADD COLUMN transportir_id bigint NULL, ADD COLUMN volume_id bigint NULL, ADD COLUMN ongkos numeric(15,2) NULL');

        DB::statement(<<<'SQL'
            UPDATE penawaran_ongkos po
            SET wilayah_id = tt.transport_area_id,
                transportir_id = tt.transporter_id,
                volume_id = tt.volume_id,
                ongkos = tt.rate
            FROM transport_tariffs tt
            WHERE tt.id = po.transport_tariff_id
        SQL);

        DB::statement('ALTER TABLE penawaran_ongkos ALTER COLUMN wilayah_id SET NOT NULL');
        DB::statement('ALTER TABLE penawaran_ongkos ALTER COLUMN transportir_id SET NOT NULL');
        DB::statement('ALTER TABLE penawaran_ongkos ALTER COLUMN ongkos SET NOT NULL');
        DB::statement('ALTER TABLE penawaran_ongkos ADD CONSTRAINT penawaran_ongkos_wilayah_id_foreign FOREIGN KEY (wilayah_id) REFERENCES transport_areas(id)');
        DB::statement('ALTER TABLE penawaran_ongkos ADD CONSTRAINT penawaran_ongkos_transportir_id_foreign FOREIGN KEY (transportir_id) REFERENCES transporters(id)');

        DB::statement('ALTER TABLE penawaran_ongkos DROP COLUMN transport_tariff_id');
    }

    private function assertUpPreconditions(): void
    {
        foreach (['penawaran_ongkos', 'quotations', 'transport_tariffs'] as $table) {
            if (! $this->tableExists($table)) {
                throw new RuntimeException("Table {$table} does not exist.");
            }
        }

        if ($this->tableExists('quotation_transports')) {
            throw new RuntimeException('Table quotation_transports already exists; refusing to rename penawaran_ongkos onto it.');
        }

        foreach (['penawaran_id', 'jenis', 'wilayah_id', 'transportir_id', 'volume_id', 'ongkos'] as $column) {
            if (! $this->columnExists('penawaran_ongkos', $column)) {
                throw new RuntimeException("Column penawaran_ongkos.{$column} not found.");
            }
        }

        if (! $this->sequenceExists('penawaran_ongkos_id_seq')) {
            throw new RuntimeException('Sequence penawaran_ongkos_id_seq not found.');
        }

        $unknownTypes = DB::table('penawaran_ongkos')
            ->whereNotNull('jenis')
            ->whereNotIn('jenis', self::LEGACY_TYPES)
            ->distinct()
            ->pluck('jenis')
            ->all();
        if ($unknownTypes !== []) {
            throw new RuntimeException('penawaran_ongkos.jenis holds unexpected values: '.implode(', ', $unknownTypes));
        }
    }

    private function assertDownPreconditions(): void
    {
        if (! $this->tableExists('quotation_transports')) {
            throw new RuntimeException('Table quotation_transports does not exist; nothing to roll back.');
        }

        if ($this->tableExists('penawaran_ongkos')) {
            throw new RuntimeException('Table penawaran_ongkos already exists; refusing to rename quotation_transports onto it.');
        }
    }

    private function ensureQuotationForeignKey(): void
    {
        $hasForeignKey = DB::selectOne(
            "select 1 as found from pg_constraint c join pg_attribute a on a.attrelid = c.conrelid and a.attnum = any(c.conkey) where c.conrelid = to_regclass('quotation_transports') and c.contype = 'f' and c.confrelid = to_regclass('quotations') and a.attname = 'quotation_id'"
        ) !== null;

        if ($hasForeignKey) {
            return;
        }

        $orphans = (int) DB::table('quotation_transports as t')
            ->leftJoin('quotations as q', 'q.id', '=', 't.quotation_id')
            ->whereNull('q.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException("{$orphans} quotation_transports rows reference a missing quotation; cannot add the foreign key.");
        }

        DB::statement('ALTER TABLE quotation_transports ADD CONSTRAINT quotation_transports_quotation_id_foreign FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE');
    }

    private function renameConstraintIfExists(string $table, string $from, string $to): void
    {
        if ($this->constraintExists($table, $from)) {
            DB::statement("ALTER TABLE {$table} RENAME CONSTRAINT {$from} TO {$to}");
        }
    }

    private function renameIndexIfExists(string $table, string $from, string $to): void
    {
        if ($this->indexExists($table, $from)) {
            DB::statement("ALTER INDEX {$from} RENAME TO {$to}");
        }
    }

    private function resolveTariffId(object $leg): ?int
    {
        if ($leg->transport_type === null || $leg->volume_id === null) {
            return null;
        }

        $candidates = DB::table('transport_tariffs')
            ->where('transporter_id', $leg->transportir_id)
            ->where('transport_area_id', $leg->wilayah_id)
            ->where('volume_id', $leg->volume_id)
            ->where('transport_type', $leg->transport_type)
            ->orderBy('id')
            ->get(['id', 'rate']);

        if ($candidates->count() > 1) {
            $sameRate = $candidates->filter(fn ($tariff) => abs((float) $tariff->rate - (float) $leg->ongkos) < 0.005)->values();

            if ($sameRate->count() !== 1) {
                throw new RuntimeException("quotation_transports row {$leg->id} stays ambiguous after matching rate to ongkos {$leg->ongkos}; tariff candidates: ".$candidates->pluck('id')->implode(','));
            }

            $candidates = $sameRate;
        }

        return $candidates->isEmpty() ? null : (int) $candidates->first()->id;
    }

    private function assertNoDuplicateLegs(): void
    {
        $duplicates = DB::table('quotation_transports')
            ->select('quotation_id', 'transport_type')
            ->groupBy('quotation_id', 'transport_type')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $pairs = $duplicates->map(fn ($row) => "{$row->quotation_id}/{$row->transport_type}")->implode(', ');

            throw new RuntimeException("quotation_transports holds more than one leg per quotation and mode: {$pairs}");
        }
    }

    private function reportUnmatched(array $rows): void
    {
        $lines = array_map(
            fn ($leg) => "id={$leg->id} quotation_id={$leg->quotation_id} transport_type=".($leg->transport_type ?? 'NULL')." transportir_id={$leg->transportir_id} wilayah_id={$leg->wilayah_id} volume_id=".($leg->volume_id ?? 'NULL')." ongkos={$leg->ongkos}",
            $rows
        );

        $summary = 'quotation_transports backfill: '.count($rows).' unmatched'.($lines === [] ? '' : ' (deleted): '.implode(' | ', $lines));
        Log::warning($summary);
        if (app()->runningInConsole()) {
            fwrite(STDOUT, $summary.PHP_EOL);
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->relationExists($table, 'r');
    }

    private function sequenceExists(string $sequence): bool
    {
        return $this->relationExists($sequence, 'S');
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
