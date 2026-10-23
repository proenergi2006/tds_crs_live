<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropUnique(['quotation_id']);
            $table->index('quotation_id');
        });
    }

    public function down(): void
    {
        $this->assertNoDuplicateQuotations();

        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropIndex(['quotation_id']);
            $table->unique('quotation_id');
        });
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

        throw new RuntimeException('Cannot restore unique quotation_id; duplicate PO found: ' . implode('; ', $lines));
    }
};
