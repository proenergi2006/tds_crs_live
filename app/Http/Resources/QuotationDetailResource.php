<?php

namespace App\Http\Resources;

use App\Enums\QuotationTransportSource;
use App\Models\QuotationItem;
use App\Support\Approval\QuotationVerifierSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationDetailResource extends JsonResource
{
    public function __construct($resource, private readonly array $approvalAttempts)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $canSeeInternals = QuotationVerifierSummary::canSeeInternals($request->user());

        $data = [
            'id'                   => $this->id,
            'nomor_penawaran'      => $this->nomor_penawaran,
            'status'               => $this->status?->value,
            'brand'                => $this->brand?->value,
            'brand_label'          => $this->brand?->label(),
            'customer_id'          => $this->customer_id,
            'branch_id'            => $this->branch_id,
            'price_period_id'      => $this->price_period_id,
            'masa_berlaku'         => $this->masa_berlaku,
            'sampai_dengan'        => $this->sampai_dengan,
            'type_pengiriman'      => $this->type_pengiriman,
            'metode'               => $this->metode,
            'lokasi_pengiriman'    => $this->lokasi_pengiriman,
            'keterangan'           => $this->keterangan,
            'tipe_pembayaran'      => $this->tipe_pembayaran,
            'acuan_pembayaran'     => $this->acuan_pembayaran,
            'dp_persen'            => $this->dp_persen,
            'dp_keterangan'        => $this->dp_keterangan,
            'repayment_persen'     => $this->repayment_persen,
            'repayment_hari'       => $this->repayment_hari,
            'order_method'         => $this->order_method,
            'toleransi_penyusutan' => $this->toleransi_penyusutan,
            'abrasi'               => $this->abrasi,
            'refund'               => $this->refund,
            'other_cost'           => $this->other_cost,
            'catatan'              => $this->catatan,
            'syarat_ketentuan'     => $this->syarat_ketentuan,
            'lampiran_tambahan'    => $this->lampiran_tambahan,
            'transport_basis'      => $this->transport_basis?->value,
            'transport_mode'       => $this->transportMode()?->value,
            'can_edit_details'     => $request->user() !== null && $this->canEditNonCommercialDetails($request->user()),
            'can_reopen'           => ($request->user()?->can('reopen', $this->resource) ?? false) && $this->isReopenable(),
            'can_manage'           => $request->user()?->can('update', $this->resource) ?? false,
        ];

        $data['customer'] = $this->customer ? [
            'company_name'        => $this->customer->company_name,
            'head_office_address' => $this->customer->headOfficeAddress ? [
                'address_line' => $this->customer->headOfficeAddress->address_line,
            ] : null,
        ] : null;

        $data['customer_contact'] = $this->customerContact ? [
            'id_contact' => $this->customerContact->id_contact,
            'full_name'  => $this->customerContact->full_name,
            'position'   => $this->customerContact->position,
            'mobile'     => $this->customerContact->mobile,
        ] : null;

        $data['branch'] = $this->branch ? [
            'nama_cabang' => $this->branch->nama_cabang,
        ] : null;

        $data['transports'] = $this->transportLegs($canSeeInternals);

        $data['items'] = $this->items->map(function ($item) use ($canSeeInternals) {
            $productPrice = $item->productPrice;

            $row = [
                'id'                => $item->id,
                'product_id'        => $item->product_id,
                'quantity'          => $item->quantity,
                'unit_price'        => $item->unit_price,
                'line_total'        => $item->line_total,
                'transport_price'   => $item->transport_price,
                'final_price'       => $item->finalPrice(),
                'final_total'       => $item->finalTotal(),
                'source_branch_id'  => $item->source_branch_id,
                'product_price_id'  => $item->product_price_id,
                'price_list'        => $productPrice?->priceListForBrand($this->brand),
                'cogs_basis'        => $productPrice?->cogs_basis,
                'source_branch'     => $item->sourceBranch
                    ? ['id' => $item->sourceBranch->id_cabang, 'nama_cabang' => $item->sourceBranch->nama_cabang]
                    : null,
                'product'           => $item->product ? [
                    'id_produk'   => $item->product->id_produk,
                    'nama_produk' => $item->product->nama_produk,
                    'jenis'       => $item->product->jenis ? ['nama' => $item->product->jenis->nama] : null,
                    'ukuran'      => $item->product->ukuran ? [
                        'nama_ukuran' => $item->product->ukuran->nama_ukuran,
                        'satuan'      => $item->product->ukuran->satuan ? ['nama_satuan' => $item->product->ukuran->satuan->nama_satuan] : null,
                    ] : null,
                ] : null,
            ];

            if ($canSeeInternals) {
                $marginMaterial  = $this->itemMarginMaterial($item);
                $marginTransport = $this->itemMarginTransport($item);

                $row['price_tier']         = $item->price_tier?->value;
                $row['tier_limits']        = [
                    'price_list' => $productPrice?->priceListForBrand($this->brand),
                    'bm_price'   => $productPrice?->bm_price,
                    'om_price'   => $productPrice?->om_price,
                    'ceo_price'  => $productPrice?->ceo_price,
                ];
                $row['below_ceo_limit']    = $this->belowCeoLimit($item);
                $row['cogs_price']         = $productPrice?->cogs_price;
                $row['transport_snapshot'] = $item->transport_snapshot;
                $row['margin_material']    = $marginMaterial;
                $row['margin_transport']   = $marginTransport;
                $row['margin']             = $marginMaterial === null || $marginTransport === null
                    ? null
                    : round($marginMaterial + $marginTransport, 2);
            }

            return $row;
        })->all();

        $data['subtotal_material'] = $this->subtotalMaterial();
        $data['transport_total']   = $this->transportTotal();
        $data['dpp']               = $this->dpp();
        $data['ppn_rate']          = (float) $this->ppn_rate;
        $data['ppn']               = $this->ppnAmount();
        $data['total']             = $this->grandTotal();

        $data = [...$data, ...QuotationVerifierSummary::for($this->resource, $request->user())];

        if ($canSeeInternals) {
            $marginMaterialTotal  = $this->marginTotal(fn ($item) => $this->itemMarginMaterial($item));
            $marginTransportTotal = $this->marginTotal(fn ($item) => $this->itemMarginTransport($item));

            $data['price_tier']             = $this->price_tier?->value;
            $data['below_ceo_limit']        = $this->items->contains(fn (QuotationItem $item) => $this->belowCeoLimit($item));
            $data['margin_material_total']  = $marginMaterialTotal;
            $data['margin_transport_total'] = $marginTransportTotal;
            $data['margin_total']           = $marginMaterialTotal === null || $marginTransportTotal === null
                ? null
                : round($marginMaterialTotal + $marginTransportTotal, 2);
        } else {
            unset($data['verifier_role']);
        }

        $data['approval_attempts'] = $this->approvalAttempts;

        return $data;
    }

    private function transportLegs(bool $canSeeInternals): array
    {
        $snapshotLegs = $this->snapshotLegs();

        return $this->transports->map(function ($row) use ($canSeeInternals, $snapshotLegs) {
            $tariff = $row->transportTariff;
            $area = $tariff?->transportArea;
            $snapshotLeg = $snapshotLegs->get($row->transport_tariff_id);

            $leg = [
                'id'                  => $row->id,
                'transport_type'      => $row->transport_type?->value,
                'transport_tariff_id' => $row->transport_tariff_id,
                'transporter_id'      => $tariff?->transporter_id,
                'transport_area_id'   => $tariff?->transport_area_id,
                'volume_id'           => $tariff?->volume_id,
                'transporter'         => $tariff?->transporter ? ['company_name' => $tariff->transporter->company_name] : null,
                'volume'              => $tariff?->volume ? ['id' => $tariff->volume->id, 'volume' => $tariff->volume->volume] : null,
                'transport_area'      => $area ? [
                    'name'      => $area->name,
                    'provinsi'  => $area->provinsi ? ['nama_provinsi' => $area->provinsi->nama_provinsi] : null,
                    'kabupaten' => $area->kabupaten ? ['nama_kabupaten' => $area->kabupaten->nama_kabupaten] : null,
                    'province'  => $area->province ? ['name' => $area->province->name] : null,
                    'regency'   => $area->regency ? ['name' => $area->regency->name] : null,
                ] : null,
                'price'               => $snapshotLeg['price'] ?? null,
            ];

            if ($canSeeInternals) {
                $leg['rate']   = $snapshotLeg['cogs'] ?? $tariff?->rate;
                $leg['margin'] = $snapshotLeg['margin'] ?? $tariff?->margin;
            }

            return $leg;
        })->all();
    }

    private function snapshotLegs(): \Illuminate\Support\Collection
    {
        $tariffItem = $this->items->first(
            fn ($item) => ($item->transport_snapshot['source'] ?? null) === QuotationTransportSource::TransportTariff->value
        );

        return collect($tariffItem?->transport_snapshot['transports'] ?? [])->keyBy('transport_tariff_id');
    }

    private function itemMarginMaterial(QuotationItem $item): ?float
    {
        $cogs = $item->productPrice?->materialCogsPrice();

        if ($cogs === null) {
            return null;
        }

        return round(((float) $item->unit_price - $cogs) * (float) $item->quantity, 2);
    }

    private function itemMarginTransport(QuotationItem $item): ?float
    {
        $margin = $item->transport_snapshot['margin'] ?? null;

        if ($margin === null) {
            return null;
        }

        return round((float) $margin * (float) $item->quantity, 2);
    }

    private function belowCeoLimit(QuotationItem $item): bool
    {
        return $item->productPrice?->isBelowCeoLimit((float) $item->unit_price) === true;
    }

    private function marginTotal(callable $perItem): ?float
    {
        $margins = $this->items->map($perItem);

        return $margins->contains(null) ? null : round($margins->sum(), 2);
    }
}
