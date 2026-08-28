<?php

namespace App\Actions\Penawaran\Concerns;

use App\Models\Penawaran;
use App\Models\PenawaranItem;

trait ManagesPenawaranLineItems
{
    private function toFloat($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float) $v;

        $s = trim((string) $v);
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return (float) $s;
        }
        if (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }
        return (float) $s;
    }

    private function calculateTotals(array $items, $discount, $oat): array
    {
        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += ((float) $it['volume_order']) * ((float) $it['harga_tebus']);
        }

        $diskon = max(0.0, min($this->toFloat($discount), $subtotal));
        $setelahDiskon = $subtotal - $diskon;

        $totalVolume = array_sum(array_column($items, 'volume_order'));
        $totalOat = $this->toFloat($oat) * (float) $totalVolume;

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

        foreach ($items as $it) {
            PenawaranItem::create([
                'id_penawaran' => $penawaran->id_penawaran,
                'id_produk'    => $it['id_produk'],
                'volume_order' => $it['volume_order'],
                'persen'       => $it['persen'],
                'harga_tebus'  => $it['harga_tebus'],
                'jumlah_harga' => $it['volume_order'] * $it['harga_tebus'],
            ]);
        }
    }
}
