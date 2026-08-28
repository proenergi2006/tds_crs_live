<?php

namespace App\Support\ProdukHarga;

use App\Models\ProdukHarga;
use Illuminate\Support\Collection;

class ActivePriceForPeriodQuery
{
    public function forProduks(array $produkIds, int $idCabang, $date): Collection
    {
        if (empty($produkIds)) {
            return collect();
        }

        return ProdukHarga::query()
            ->whereIn('id_produk', $produkIds)
            ->where('id_cabang', $idCabang)
            ->whereDate('periode_awal', '<=', $date)
            ->whereDate('periode_akhir', '>=', $date)
            ->orderByDesc('periode_akhir')
            ->get()
            ->groupBy('id_produk')
            ->map(fn ($rows) => $rows->first());
    }
}
