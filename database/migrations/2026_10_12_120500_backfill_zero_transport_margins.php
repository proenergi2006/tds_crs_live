<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transport_tariffs')->whereNull('margin')->update(['margin' => 0]);

        DB::table('product_prices')
            ->where('cogs_basis', 'franco')
            ->whereNotNull('cogs_transport_price')
            ->whereNull('margin_transport_amount')
            ->whereIn('price_period_id', DB::table('price_periods')->select('id')->where('end_date', '>=', now()->toDateString()))
            ->update(['margin_transport_amount' => 0]);
    }

    public function down(): void
    {
        // sengaja kosong: margin 0 hasil backfill gak bisa dibedain dari 0 yang diisi Logistik
    }
};
