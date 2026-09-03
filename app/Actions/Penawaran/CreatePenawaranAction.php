<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ManagesPenawaranLineItems;
use App\Models\Cabang;
use App\Models\Penawaran;
use App\Models\PricePeriod;
use App\Models\User;
use App\Support\RomanMonth;
use Illuminate\Support\Facades\DB;

class CreatePenawaranAction
{
    use ManagesPenawaranLineItems;

    public function execute(array $data, string $brand, User $user): Penawaran
    {
        return DB::transaction(function () use ($data, $brand, $user) {
            $data['user_id'] = $user->id ?? ($data['user_id'] ?? null);
            $data['brand'] = $brand;

            $cabang = Cabang::lockForUpdate()->findOrFail($data['id_cabang']);

            $period = PricePeriod::findOrFail($data['price_period_id']);
            $data['masa_berlaku'] = $period->start_date->format('Y-m-d');
            $data['sampai_dengan'] = $period->end_date->format('Y-m-d');
            $data['items'] = $this->resolveItemPrices($data['items'], $brand);

            $data = array_merge($data, $this->calculateTotals($data['items'], $data['discount'] ?? 0, $data['oat'] ?? 0));
            $data['nomor_penawaran'] = $this->generateNomor($cabang, $brand);
            $data['status'] = 'draft';
            $data['disposisi_penawaran'] = '1';
            $data['type_pengiriman'] = $data['type_pengiriman'] ?? null;
            $data['created_at'] = now();
            $data['created_by'] = optional($user)->name;

            $penawaran = Penawaran::create($data);

            $this->replaceOngkos($penawaran, $data['ongkos'] ?? []);
            $this->replaceItems($penawaran, $data['items']);

            $penawaran->refresh();

            return $penawaran->load(['customer', 'cabang', 'items.produk']);
        });
    }

    private function generateNomor(Cabang $cabang, string $brand): string
    {
        $urut = (int) $cabang->urut_penawaran + 1;
        $prefix = $brand === 'proenergi' ? 'PE-PN' : 'TDS-PN';
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
            . "/{$prefix}/" . $cabang->inisial_cabang . '/' . RomanMonth::of(date('m')) . '/' . substr(date('Y'), -2);

        $cabang->urut_penawaran = $urut;
        $cabang->save();

        return $nomor;
    }
}
