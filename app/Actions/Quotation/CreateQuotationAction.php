<?php

namespace App\Actions\Quotation;

use App\Actions\Quotation\Concerns\ManagesQuotationLineItems;
use App\Enums\QuotationStatus;
use App\Models\Cabang;
use App\Models\Quotation;
use App\Models\PricePeriod;
use App\Models\User;
use App\Support\RomanMonth;
use Illuminate\Support\Facades\DB;

class CreateQuotationAction
{
    use ManagesQuotationLineItems;

    public function __construct(private readonly ResolveQuotationTransportPricingAction $resolveTransportPricing)
    {
    }

    public function execute(array $data, string $brand, User $user): Quotation
    {
        $pricing = $this->resolveTransportPricing->execute(
            $data['items'],
            $data['transport_mode'] ?? null,
            $data['transports'] ?? []
        );
        $this->resolveTransportPricing->assertMetodeMatchesBasis($pricing['basis'], $data['metode'] ?? null);

        return DB::transaction(function () use ($data, $brand, $user, $pricing) {
            $data['user_id'] = $user->id ?? ($data['user_id'] ?? null);
            $data['brand'] = $brand;

            $cabang = Cabang::lockForUpdate()->findOrFail($data['branch_id']);

            $period = PricePeriod::findOrFail($data['price_period_id']);
            $data['masa_berlaku'] = $period->start_date->format('Y-m-d');
            $data['sampai_dengan'] = $period->end_date->format('Y-m-d');
            $data['items'] = $this->applyTransportPricing($this->resolveItemPrices($data['items']), $pricing);
            $data['transport_basis'] = $pricing['basis'];

            $data['nomor_penawaran'] = $this->generateNomor($cabang, $brand);
            $data['status'] = QuotationStatus::Draft;
            $data['type_pengiriman'] = $data['type_pengiriman'] ?? null;
            $data['created_at'] = now();
            $data['created_by'] = optional($user)->name;

            $quotation = Quotation::create($data);

            $this->replaceTransports($quotation, $pricing['transports']);
            $this->replaceItems($quotation, $data['items']);

            $quotation->refresh();

            return $quotation->load(['customer', 'branch', 'items.product']);
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
