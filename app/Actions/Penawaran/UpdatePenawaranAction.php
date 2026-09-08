<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ManagesPenawaranLineItems;
use App\Models\Penawaran;
use App\Models\PricePeriod;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class UpdatePenawaranAction
{
    use ManagesPenawaranLineItems;

    public function execute(Penawaran $penawaran, array $data, User $user): Penawaran
    {
        return DB::transaction(function () use ($penawaran, $data, $user) {
            $brand = $penawaran->brand?->value ?? 'tds';

            $period = PricePeriod::findOrFail($data['price_period_id']);
            $data['masa_berlaku'] = $period->start_date->format('Y-m-d');
            $data['sampai_dengan'] = $period->end_date->format('Y-m-d');
            $data['items'] = $this->resolveItemPrices($data['items'], $brand);

            $data = array_merge($data, $this->calculateTotals($data['items'], $data['discount'] ?? 0, $data['oat'] ?? 0));
            $data['type_pengiriman'] = $data['type_pengiriman'] ?? $penawaran->type_pengiriman;
            $data['updated_at'] = now();
            $data['updated_by'] = optional($user)->name;

            $penawaran->update($data);

            if ($penawaran->status !== 'draft') {
                (new DocumentApprovalService())->cancelActiveCycle($penawaran);
            }

            $penawaran->forceFill([
                'status'              => 'draft',
                'disposisi_penawaran' => '1',
            ])->save();

            $this->replaceOngkos($penawaran, $data['ongkos'] ?? []);
            $this->replaceItems($penawaran, $data['items']);

            return $penawaran->load(['customer', 'cabang', 'items.produk']);
        });
    }
}
