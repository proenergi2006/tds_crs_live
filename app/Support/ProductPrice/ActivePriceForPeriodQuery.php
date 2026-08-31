<?php

namespace App\Support\ProductPrice;

use App\Models\ProductPrice;
use Illuminate\Support\Collection;

class ActivePriceForPeriodQuery
{
    public function forProducts(array $productIds, int $branchId, $date): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return ProductPrice::query()
            ->join('price_periods', 'price_periods.id', '=', 'product_prices.price_period_id')
            ->whereIn('product_prices.product_id', $productIds)
            ->where('product_prices.branch_id', $branchId)
            ->whereDate('price_periods.start_date', '<=', $date)
            ->whereDate('price_periods.end_date', '>=', $date)
            ->orderByDesc('price_periods.end_date')
            ->orderByDesc('product_prices.created_at')
            ->select('product_prices.*')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->first());
    }
}
