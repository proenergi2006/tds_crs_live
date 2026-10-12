<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const PRICE_COLUMNS = ['price_list', 'price_list_pe', 'bm_price', 'om_price', 'ceo_price'];

    public function up(): void
    {
        $rows = DB::table('product_prices as pp')
            ->join('price_periods as per', 'per.id', '=', 'pp.price_period_id')
            ->where('pp.cogs_basis', 'franco')
            ->where('pp.cogs_transport_price', '>', 0)
            ->where('per.end_date', '>=', now()->toDateString())
            ->select('pp.id', 'pp.cogs_material_price', 'pp.cogs_transport_price', ...array_map(fn ($c) => "pp.{$c}", self::PRICE_COLUMNS))
            ->get();

        $converted = [];
        $skipped = [];

        foreach ($rows as $row) {
            $transport = (float) $row->cogs_transport_price;
            $material = (float) $row->cogs_material_price;
            $ceoAfter = (float) $row->ceo_price - $transport;

            if ($material <= 0 || ((float) $row->ceo_price > 0 && $ceoAfter < $material)) {
                $skipped[] = $row->id;
                continue;
            }

            $update = [];
            foreach (self::PRICE_COLUMNS as $column) {
                $value = (float) $row->{$column};
                if ($value > 0) {
                    $update[$column] = round($value - $transport, 2);
                }
            }

            DB::table('product_prices')->where('id', $row->id)->update($update);
            $converted[] = $row->id;
        }

        $summary = 'Franco price lists converted to material-only: ['.implode(',', $converted).']; skipped: ['.implode(',', $skipped).']';
        Log::info($summary);
        if (app()->runningInConsole()) {
            fwrite(STDOUT, $summary.PHP_EOL);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Konversi price list franco ke material-only tidak dapat di-rollback otomatis; pulihkan dari backup.');
    }
};
