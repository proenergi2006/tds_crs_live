<?php

namespace App\Support\ProductPrice;

use App\Models\PricePeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PricePeriodCompletenessQuery
{
    public function groupedCurrentAndUpcoming(): Collection
    {
        $today = Carbon::today();

        return $this->grouped()
            ->filter(fn ($row) => Carbon::parse($row->end_date)->gte($today))
            ->values();
    }

    public function grouped(): Collection
    {
        return PricePeriod::query()
            ->leftJoin('product_prices', 'product_prices.price_period_id', '=', 'price_periods.id')
            ->selectRaw(
                'price_periods.id, price_periods.start_date, price_periods.end_date, ' .
                    'COUNT(product_prices.id) AS jumlah_data, ' .
                    'COUNT(DISTINCT product_prices.branch_id) AS jumlah_cabang, ' .
                    'MAX(COALESCE(product_prices.updated_at, product_prices.created_at)) AS terakhir_diupdate, ' .
                    'SUM(CASE WHEN product_prices.ceo_price IS NULL OR product_prices.ceo_price = 0 OR product_prices.margin_amount = 0 OR product_prices.price_list = 0 THEN 1 ELSE 0 END) AS jumlah_belum_lengkap'
            )
            ->groupBy('price_periods.id', 'price_periods.start_date', 'price_periods.end_date')
            ->orderBy('price_periods.start_date', 'desc')
            ->orderBy('price_periods.end_date', 'desc')
            ->get();
    }
}
