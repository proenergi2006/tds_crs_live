<?php

namespace App\Actions\Quotation;

use App\Actions\Quotation\Traits\ManagesQuotationLineItems;
use App\Enums\QuotationBrand;
use App\Enums\QuotationStatus;
use App\Models\Cabang;
use App\Models\PricePeriod;
use App\Models\Quotation;
use App\Models\User;
use App\Support\RomanMonth;
use Illuminate\Support\Facades\DB;

class CreateQuotationAction
{
    use ManagesQuotationLineItems;

    public function __construct(private readonly ResolveQuotationTransportPricingAction $resolveTransportPricing)
    {
    }

    public function execute(array $data, QuotationBrand $brand, User $user): Quotation
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

            return $quotation->refresh();
        });
    }

    private function generateNomor(Cabang $cabang, QuotationBrand $brand): string
    {
        $urut = (int) $cabang->urut_penawaran + 1;
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
            . "/{$brand->numberPrefix()}/" . $cabang->inisial_cabang . '/' . RomanMonth::of(date('m')) . '/' . substr(date('Y'), -2);

        $cabang->urut_penawaran = $urut;
        $cabang->save();

        return $nomor;
    }
}
