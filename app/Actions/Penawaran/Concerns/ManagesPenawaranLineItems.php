<?php

namespace App\Actions\Penawaran\Concerns;

use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\ProductPrice;

trait ManagesPenawaranLineItems
{
    private function resolveItemPrices(array $items, string $brand): array
    {
        $priceRows = ProductPrice::whereIn('id', array_column($items, 'product_price_id'))->get()->keyBy('id');

        return array_map(function ($item) use ($priceRows, $brand) {
            $productPrice = $priceRows[(int) $item['product_price_id']];
            $hargaTebus = (int) round($productPrice->priceListForBrand($brand) * ((float) $item['persen']) / 100);

            $item['harga_tebus'] = $hargaTebus;
            $item['jumlah_harga'] = ((float) $item['volume_order']) * $hargaTebus;

            return $item;
        }, $items);
    }

    private function calculateTotals(array $items, $discount, $oat): array
    {
        $subtotal = array_sum(array_column($items, 'jumlah_harga'));

        $diskon = max(0.0, min((float) $discount, $subtotal));
        $setelahDiskon = $subtotal - $diskon;

        $totalVolume = array_sum(array_column($items, 'volume_order'));
        $totalOat = (float) $oat * (float) $totalVolume;

        $ppn11 = round($setelahDiskon * 0.11, 2);
        $total = $setelahDiskon + $ppn11;

        return [
            'subtotal'                   => $subtotal,
            'discount'                   => $diskon,
            'harga_tebus_setelah_diskon' => $setelahDiskon,
            'ppn11'                      => $ppn11,
            'total'                      => $total,
            'total_with_oat'             => $total + $totalOat,
        ];
    }

    private function replaceOngkos(Penawaran $penawaran, array $ongkos): void
    {
        $penawaran->ongkos()->delete();

        foreach ($ongkos as $o) {
            $penawaran->ongkos()->create([
                'penawaran_id'   => $penawaran->id_penawaran,
                'wilayah_id'     => $o['id_angkut_wilayah'],
                'transportir_id' => $o['id_transportir'],
                'jenis'          => $o['jenis'],
                'volume_id'      => $o['id_volume'],
                'ongkos'         => $o['ongkos'],
            ]);
        }
    }

    private function replaceItems(Penawaran $penawaran, array $items): void
    {
        PenawaranItem::where('id_penawaran', $penawaran->id_penawaran)->delete();

        foreach ($items as $item) {
            PenawaranItem::create([
                'id_penawaran'     => $penawaran->id_penawaran,
                'id_produk'        => $item['id_produk'],
                'source_branch_id' => $item['source_branch_id'],
                'product_price_id' => $item['product_price_id'],
                'volume_order'     => $item['volume_order'],
                'persen'           => $item['persen'],
                'harga_tebus'      => $item['harga_tebus'],
                'jumlah_harga'     => $item['jumlah_harga'],
            ]);
        }
    }
}
