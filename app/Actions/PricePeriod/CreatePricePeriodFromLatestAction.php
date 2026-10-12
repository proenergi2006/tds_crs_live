<?php

namespace App\Actions\PricePeriod;

use App\Models\PricePeriod;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;

class CreatePricePeriodFromLatestAction
{
    private const COPIED_COLUMNS = [
        'branch_id',
        'product_id',
        'cogs_basis',
        'cogs_price',
        'cogs_material_price',
        'cogs_transport_price',
        'price_list',
        'price_list_pe',
        'margin_material_amount',
        'margin_transport_amount',
        'bm_price',
        'om_price',
        'ceo_price',
    ];

    public function findSourcePeriod(): ?PricePeriod
    {
        return PricePeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    public function execute(PricePeriod $source, array $periodData, array $attachments, string $userName): array
    {
        return DB::transaction(function () use ($source, $periodData, $attachments, $userName) {
            $period = PricePeriod::create([
                'start_date'  => $periodData['start_date'],
                'end_date'    => $periodData['end_date'],
                'notes'       => $periodData['notes'] ?? null,
                'attachments' => $attachments,
                'created_by'  => $userName,
            ]);

            $sourceCount = ProductPrice::where('price_period_id', $source->id)->count();

            $activeRows = ProductPrice::query()
                ->where('price_period_id', $source->id)
                ->whereHas('produk', fn ($query) => $query->where('is_active', true))
                ->whereHas('cabang', fn ($query) => $query->where('is_active', true))
                ->get();

            $copiedIds = $activeRows->map(function (ProductPrice $row) use ($period, $userName) {
                return ProductPrice::create(array_merge($row->only(self::COPIED_COLUMNS), [
                    'price_period_id'      => $period->id,
                    'needs_reverification' => true,
                    'notes'                => null,
                    'created_by'           => $userName,
                    'updated_by'           => null,
                ]))->id;
            });

            return [
                'period'         => $period->fresh(),
                'product_prices' => ProductPrice::whereIn('id', $copiedIds)
                    ->with(['pricePeriod', 'cabang', 'produk.ukuran.satuan'])
                    ->get(),
                'duplicated_count'       => $copiedIds->count(),
                'skipped_inactive_count' => $sourceCount - $copiedIds->count(),
            ];
        });
    }
}
