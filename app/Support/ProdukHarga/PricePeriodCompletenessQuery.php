<?php

namespace App\Support\ProdukHarga;

use App\Models\ProdukHarga;
use Illuminate\Support\Collection;

class PricePeriodCompletenessQuery
{
    public function grouped(): Collection
    {
        return ProdukHarga::query()
            ->selectRaw(
                'periode_awal, periode_akhir, ' .
                    'COUNT(*) AS jumlah_data, ' .
                    'COUNT(DISTINCT id_cabang) AS jumlah_cabang, ' .
                    'MAX(COALESCE(lastupdate_time, created_time)) AS terakhir_diupdate, ' .
                    'SUM(CASE WHEN harga_ceo IS NULL OR harga_ceo = 0 OR harga_margin = 0 OR harga_price_list = 0 THEN 1 ELSE 0 END) AS jumlah_belum_lengkap'
            )
            ->groupBy('periode_awal', 'periode_akhir')
            ->orderBy('periode_awal', 'desc')
            ->orderBy('periode_akhir', 'desc')
            ->get();
    }
}
