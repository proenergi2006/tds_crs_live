<?php

namespace App\Support\Dashboard;

use App\Models\Penawaran;
use App\Models\Produk;
use App\Models\ProdukHarga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StalePenawaranPriceQuery
{
    // "stale" = periode harga terbaru produknya udah lewat hari ini, bukan cek harga pas quote dibuat (gak kesimpen)
    public function summarize(int $limit = 10): array
    {
        $pendingPenawarans = Penawaran::whereIn('disposisi_penawaran', [2, 3])
            ->with('items')
            ->get();

        $produkIds = $pendingPenawarans
            ->flatMap(fn ($penawaran) => $penawaran->items)
            ->pluck('id_produk')
            ->unique()
            ->values();

        if ($produkIds->isEmpty()) {
            return ['total' => 0, 'items' => []];
        }

        $latestPeriodePerProduk = ProdukHarga::whereIn('id_produk', $produkIds)
            ->groupBy('id_produk')
            ->select('id_produk', DB::raw('MAX(periode_akhir) as latest_periode_akhir'))
            ->get()
            ->keyBy('id_produk');

        $staleProdukIds = $latestPeriodePerProduk
            ->filter(fn ($row) => Carbon::parse($row->latest_periode_akhir)->lt(now()))
            ->keys();

        if ($staleProdukIds->isEmpty()) {
            return ['total' => 0, 'items' => []];
        }

        $produkNames = Produk::whereIn('id_produk', $staleProdukIds)->pluck('nama_produk', 'id_produk');

        $matches = $pendingPenawarans
            ->map(function ($penawaran) use ($staleProdukIds, $latestPeriodePerProduk, $produkNames) {
                $staleItem = $penawaran->items->first(fn ($item) => $staleProdukIds->contains($item->id_produk));

                if (!$staleItem) {
                    return null;
                }

                return [
                    'id_penawaran'    => $penawaran->id_penawaran,
                    'nomor_penawaran' => $penawaran->nomor_penawaran,
                    'produk_name'     => $produkNames->get($staleItem->id_produk),
                    'periode_akhir'   => $latestPeriodePerProduk->get($staleItem->id_produk)->latest_periode_akhir,
                ];
            })
            ->filter()
            ->sortBy('periode_akhir')
            ->values();

        return [
            'total' => $matches->count(),
            'items' => $matches->take($limit)->all(),
        ];
    }
}
