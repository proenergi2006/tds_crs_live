<?php

namespace App\Actions\Quotation;

use App\Actions\Quotation\Traits\ManagesQuotationLineItems;
use App\Enums\QuotationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PricePeriod;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateQuotationAction
{
    use ManagesQuotationLineItems;

    public function __construct(private readonly ResolveQuotationTransportPricingAction $resolveTransportPricing)
    {
    }

    public function execute(Quotation $quotation, array $data, User $user): array
    {
        if ($quotation->status !== QuotationStatus::Draft) {
            throw new BusinessRuleException('Hanya penawaran draft yang bisa diubah.');
        }

        $quotation->loadMissing(['items', 'transports']);

        $recalculate = $this->transportSelectionChanged($quotation, $data);
        $pricing     = $recalculate
            ? $this->resolveTransportPricing->execute($data['items'], $data['transport_mode'] ?? null, $data['transports'] ?? [])
            : null;

        $this->resolveTransportPricing->assertMetodeMatchesBasis(
            $pricing['basis'] ?? $quotation->transport_basis ?? $this->resolveTransportPricing->detectBasis($data['items']),
            $data['metode'] ?? null
        );

        $updated = DB::transaction(function () use ($quotation, $data, $user, $recalculate, $pricing) {
            $period = PricePeriod::findOrFail($data['price_period_id']);
            $data['masa_berlaku'] = $period->start_date->format('Y-m-d');
            $data['sampai_dengan'] = $period->end_date->format('Y-m-d');
            $data['items'] = $this->resolveItemPrices($data['items']);

            if ($recalculate) {
                $data['items'] = $this->applyTransportPricing($data['items'], $pricing);
                $data['transport_basis'] = $pricing['basis'];
            } else {
                $data['items'] = $this->carryOverTransportPricing($data['items'], $quotation->items);
            }

            $data['type_pengiriman'] = $data['type_pengiriman'] ?? $quotation->type_pengiriman;
            $data['updated_at'] = now();
            $data['updated_by'] = optional($user)->name;

            $quotation->update($data);

            $quotation->forceFill(['price_tier' => null])->save();

            if ($recalculate) {
                $this->replaceTransports($quotation, $pricing['transports']);
            }

            $this->replaceItems($quotation, $data['items']);

            return $quotation->unsetRelations()->load(['customer', 'branch', 'items.product', 'transports']);
        });

        return ['quotation' => $updated, 'transport_recalculated' => $recalculate];
    }

    private function transportSelectionChanged(Quotation $quotation, array $data): bool
    {
        if ($this->itemCompositionKeys($quotation->items) !== $this->itemCompositionKeys($data['items'])) {
            return true;
        }

        if ($this->tariffSelectionKeys($quotation->transports) !== $this->tariffSelectionKeys($data['transports'] ?? [])) {
            return true;
        }

        $hasBreakdown = $quotation->items->contains(fn ($item) => $item->transport_snapshot !== null);

        return $hasBreakdown && $quotation->transportMode()?->value !== ($data['transport_mode'] ?? null);
    }

    private function itemCompositionKeys(iterable $items): array
    {
        $keys = [];

        foreach ($items as $item) {
            $keys[] = (int) data_get($item, 'product_id')
                . '|' . (int) data_get($item, 'source_branch_id')
                . '|' . (int) data_get($item, 'product_price_id');
        }

        sort($keys);

        return $keys;
    }

    private function tariffSelectionKeys(iterable $transports): array
    {
        $keys = [];

        foreach ($transports as $transport) {
            $type = data_get($transport, 'transport_type');
            $type = $type instanceof \BackedEnum ? $type->value : $type;

            $keys[] = $type . '|' . (int) data_get($transport, 'transport_tariff_id');
        }

        sort($keys);

        return $keys;
    }

    private function carryOverTransportPricing(array $items, Collection $storedItems): array
    {
        $stored = $storedItems->keyBy(fn ($item) => $this->itemCompositionKeys([$item])[0]);

        foreach ($items as $index => $item) {
            $match = $stored->get($this->itemCompositionKeys([$item])[0]);

            $items[$index]['transport_price']    = $match?->transport_price;
            $items[$index]['transport_snapshot'] = $match?->transport_snapshot;
        }

        return $items;
    }
}
