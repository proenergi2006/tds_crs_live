<?php

namespace App\Http\Requests\PoCustomer\Traits;

use App\Models\Quotation;
use Illuminate\Validation\Validator;

trait ValidatesPoCustomerItems
{
    private function itemRules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.quotation_item_id' => ['required', 'integer', 'distinct'],
            'items.*.volume'            => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    private function hasItemErrors(Validator $validator): bool
    {
        return collect($validator->errors()->keys())
            ->contains(fn (string $key) => $key === 'items' || str_starts_with($key, 'items.'));
    }

    private function validateItems(Validator $validator, Quotation $quotation, array $storedVolumes = []): void
    {
        $quotationItems = $quotation->items->keyBy('id');
        $payloadItems   = array_values((array) $this->input('items', []));
        $hasItemError   = false;
        $totalCents     = 0;

        foreach ($payloadItems as $index => $payloadItem) {
            $quotationItem = $quotationItems->get((int) $payloadItem['quotation_item_id']);

            if ($quotationItem === null) {
                $validator->errors()->add(
                    "items.{$index}.quotation_item_id",
                    'Item tidak termasuk dalam penawaran ini.'
                );
                $hasItemError = true;

                continue;
            }

            $volumeCents = $this->toCents($payloadItem['volume']);
            $limitCents  = max(
                $this->toCents($quotationItem->quantity),
                $this->toCents($storedVolumes[$quotationItem->id] ?? 0)
            );

            if ($volumeCents > $limitCents) {
                $quantityLabel = rtrim(rtrim(number_format((float) $quotationItem->quantity, 2, ',', '.'), '0'), ',');

                $validator->errors()->add(
                    "items.{$index}.volume",
                    "Volume melebihi kuantitas penawaran ({$quantityLabel})."
                );
                $hasItemError = true;
            }

            $totalCents += $volumeCents;
        }

        if (! $hasItemError && $totalCents === 0) {
            $validator->errors()->add('items', 'Minimal satu item harus memiliki volume lebih dari 0.');
        }
    }

    private function toCents(string|int|float $volume): int
    {
        return (int) round((float) $volume * 100);
    }
}
