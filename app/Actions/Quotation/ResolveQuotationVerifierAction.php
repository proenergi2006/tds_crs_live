<?php

namespace App\Actions\Quotation;

use App\Enums\PriceTier;
use App\Enums\QuotationVerifierRole;
use App\Models\Quotation;
use Illuminate\Validation\ValidationException;

class ResolveQuotationVerifierAction
{
    private const JENIS_PRODUK_POLIMER = 11;

    public function execute(Quotation $quotation): array
    {
        $quotation->loadMissing('items.productPrice', 'items.product');

        if ($quotation->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Penawaran belum memiliki item.']);
        }

        $itemTiers = [];

        foreach ($quotation->items->values() as $index => $item) {
            $limits = $item->productPrice;
            $key    = "items.{$index}.product_price_id";

            if (! $limits || (int) $limits->price_period_id !== (int) $quotation->price_period_id) {
                throw ValidationException::withMessages([$key => 'Harga produk tidak sesuai periode harga Penawaran.']);
            }

            foreach (['bm_price', 'om_price', 'ceo_price'] as $column) {
                if ((float) $limits->{$column} <= 0) {
                    throw ValidationException::withMessages([$key => 'Batas tier harga produk ini belum lengkap.']);
                }
            }

            $itemTiers[$item->id] = PriceTier::forUnitPrice((float) $item->unit_price, $limits);
        }

        $tier      = PriceTier::highest($itemTiers);
        $isPolimer = $quotation->items->contains(fn ($item) => (int) $item->product?->id_jenis === self::JENIS_PRODUK_POLIMER);

        return [
            'item_tiers' => $itemTiers,
            'tier'       => $tier,
            'verifier'   => QuotationVerifierRole::forQuotation($quotation->brand, $isPolimer, $tier),
        ];
    }
}
