<?php

namespace App\Actions\Penawaran\Concerns;

use App\Enums\PenawaranBrand;
use Illuminate\Database\Eloquent\Model;

trait ResolvesApprovalTemplate
{
    private const JENIS_PRODUK_POLIMER = 11;

    private function templateCodeFor(Model $penawaran): string
    {
        if ($penawaran->brand === PenawaranBrand::Proenergi) {
            return 'penawaran_proenergi';
        }

        $isPolimer = $penawaran->items()
            ->whereHas('produk', fn ($q) => $q->where('id_jenis', self::JENIS_PRODUK_POLIMER))
            ->exists();

        return $isPolimer ? 'penawaran_polimer' : 'penawaran_tds';
    }

    private function requiredStepOrdersFor(Model $penawaran): array
    {
        $items = $penawaran->items()->with('productPrice')->get();
        $totalPersen = (float) $items->sum('persen');

        if ($totalPersen <= 0) {
            return [1, 2];
        }

        $weightedBmPrice = $items->sum(
            fn ($item) => (float) ($item->productPrice?->bm_price ?? 0) * (float) $item->persen
        ) / $totalPersen;

        return ((float) $penawaran->harga_dasar) >= $weightedBmPrice ? [1] : [1, 2];
    }
}
