<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->columnExists('document_approval_steps', 'id_role')) {
            throw new RuntimeException('Column document_approval_steps.id_role already exists.');
        }

        DB::statement('ALTER TABLE document_approval_steps ADD COLUMN id_role bigint NULL');
        DB::statement('UPDATE document_approval_steps s SET id_role = t.id_role FROM approval_template_steps t WHERE t.id_step = s.id_template_step');

        $orphans = DB::table('document_approval_steps')->whereNull('id_role');
        $orphanCount = (clone $orphans)->count();

        if ($orphanCount > 0) {
            $sample = (clone $orphans)->orderBy('id')->limit(5)->pluck('id')->implode(', ');
            throw new RuntimeException("{$orphanCount} document_approval_steps rows have no role in their template step (ids: {$sample}); id_role cannot be NOT NULL.");
        }

        DB::statement('ALTER TABLE document_approval_steps ALTER COLUMN id_role SET NOT NULL');
        DB::statement('ALTER TABLE document_approval_steps ADD CONSTRAINT document_approval_steps_id_role_foreign FOREIGN KEY (id_role) REFERENCES roles(id) ON DELETE RESTRICT');
        DB::statement('CREATE INDEX document_approval_steps_id_role_index ON document_approval_steps (id_role)');

        DB::statement('ALTER TABLE document_approvals ALTER COLUMN id_template DROP NOT NULL');
        DB::statement('ALTER TABLE document_approval_steps ALTER COLUMN id_template_step DROP NOT NULL');
    }

    public function down(): void
    {
        if (! $this->columnExists('document_approval_steps', 'id_role')) {
            throw new RuntimeException('Column document_approval_steps.id_role not found.');
        }

        $cyclesWithoutTemplate = DB::table('document_approvals')->whereNull('id_template')->count();
        $stepsWithoutTemplate = DB::table('document_approval_steps')->whereNull('id_template_step')->count();

        if ($cyclesWithoutTemplate > 0 || $stepsWithoutTemplate > 0) {
            throw new RuntimeException("Cycles without template exist (document_approvals: {$cyclesWithoutTemplate}, document_approval_steps: {$stepsWithoutTemplate}); handle them before rolling back.");
        }

        DB::statement('ALTER TABLE document_approvals ALTER COLUMN id_template SET NOT NULL');
        DB::statement('ALTER TABLE document_approval_steps ALTER COLUMN id_template_step SET NOT NULL');

        DB::statement('DROP INDEX document_approval_steps_id_role_index');
        DB::statement('ALTER TABLE document_approval_steps DROP CONSTRAINT document_approval_steps_id_role_foreign');
        DB::statement('ALTER TABLE document_approval_steps DROP COLUMN id_role');
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            'select 1 as found from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        ) !== null;
    }
};
