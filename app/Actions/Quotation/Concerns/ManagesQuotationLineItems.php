<?php

namespace App\Actions\Quotation\Concerns;

use App\Models\Quotation;
use App\Models\QuotationItem;

trait ManagesQuotationLineItems
{
    private function resolveItemPrices(array $items): array
    {
        return array_map(function ($item) {
            $item['line_total'] = round((float) $item['unit_price'] * (float) $item['quantity'], 2);

            return $item;
        }, $items);
    }

    private function applyTransportPricing(array $items, array $pricing): array
    {
        foreach ($items as $index => $item) {
            $items[$index]['transport_price']    = $pricing['items'][$index]['transport_price'];
            $items[$index]['transport_snapshot'] = $pricing['items'][$index]['transport_snapshot'];
        }

        return $items;
    }

    private function replaceTransports(Quotation $quotation, array $transports): void
    {
        $quotation->transports()->delete();

        foreach ($transports as $transport) {
            $quotation->transports()->create([
                'transport_type'      => $transport['transport_type'],
                'transport_tariff_id' => $transport['transport_tariff_id'],
            ]);
        }
    }

    private function replaceItems(Quotation $quotation, array $items): void
    {
        QuotationItem::where('quotation_id', $quotation->id)->delete();

        foreach ($items as $item) {
            QuotationItem::create([
                'quotation_id'     => $quotation->id,
                'product_id'       => $item['product_id'],
                'source_branch_id' => $item['source_branch_id'],
                'product_price_id' => $item['product_price_id'],
                'quantity'         => $item['quantity'],
                'unit_price'       => $item['unit_price'],
                'line_total'       => $item['line_total'],
                'transport_price'    => $item['transport_price'],
                'transport_snapshot' => $item['transport_snapshot'],
            ]);
        }
    }
}
