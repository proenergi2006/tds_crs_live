<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE penawarans p
            SET price_period_id = pp.id
            FROM price_periods pp
            WHERE pp.start_date = p.masa_berlaku
              AND pp.end_date = p.sampai_dengan
              AND p.price_period_id IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('UPDATE penawarans SET price_period_id = NULL');
    }
};
