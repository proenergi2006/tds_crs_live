<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE penawaran_items pi
            SET source_branch_id = p.id_cabang
            FROM penawarans p
            WHERE p.id_penawaran = pi.id_penawaran
              AND pi.source_branch_id IS NULL
        ');

        DB::statement('
            UPDATE penawaran_items pi
            SET product_price_id = (
                SELECT pp.id
                FROM product_prices pp
                JOIN price_periods per ON per.id = pp.price_period_id
                JOIN penawarans p ON p.id_penawaran = pi.id_penawaran
                WHERE pp.product_id = pi.id_produk
                  AND pp.branch_id = pi.source_branch_id
                  AND per.start_date <= p.masa_berlaku
                  AND per.end_date   >= p.masa_berlaku
                ORDER BY per.end_date DESC, pp.created_at DESC
                LIMIT 1
            )
            WHERE pi.product_price_id IS NULL
              AND pi.source_branch_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('UPDATE penawaran_items SET source_branch_id = NULL, product_price_id = NULL');
    }
};
