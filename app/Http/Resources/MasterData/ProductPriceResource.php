<?php

namespace App\Http\Resources\MasterData;

use App\Enums\ProductPriceCogsBasis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewInternal = (bool) $request->user()?->can('price-period.view');

        return [
            'id'             => $this->id,
            'price_period_id' => $this->price_period_id,
            'branch_id'      => $this->branch_id,
            'product_id'     => $this->product_id,
            'price_list'     => $this->price_list,
            'price_list_pe'  => $this->price_list_pe,
            $this->mergeWhen($canViewInternal, fn() => [
                'bm_price'         => $this->bm_price,
                'cogs_price'       => $this->cogs_price,
                'cogs_basis'       => $this->cogs_basis,
                'cogs_basis_label' => $this->cogs_basis ? ProductPriceCogsBasis::from($this->cogs_basis)->label() : null,
                'margin_amount'    => $this->margin_amount,
                'om_price'         => $this->om_price,
                'ceo_price'        => $this->ceo_price,
                'notes'            => $this->notes,
                'created_at'       => $this->created_at,
                'created_by'       => $this->created_by,
                'updated_at'       => $this->updated_at,
                'updated_by'       => $this->updated_by,
            ]),
            'price_period'   => $this->whenLoaded('pricePeriod', fn() => [
                'id'         => $this->pricePeriod->id,
                'start_date' => optional($this->pricePeriod->start_date)->format('Y-m-d'),
                'end_date'   => optional($this->pricePeriod->end_date)->format('Y-m-d'),
            ]),
            'branch'         => $this->whenLoaded('cabang', fn() => [
                'id'   => $this->cabang->id_cabang,
                'name' => $this->cabang->nama_cabang,
            ]),
            'product'        => $this->whenLoaded('produk', fn() => [
                'id'          => $this->produk->id_produk,
                'nama_produk' => $this->produk->nama_produk,
                'merk_dagang' => $this->produk->merk_dagang,
                'ukuran'      => $this->produk->relationLoaded('ukuran') && $this->produk->ukuran
                    ? [
                        'id_ukuran'   => $this->produk->ukuran->id_ukuran,
                        'nama_ukuran' => $this->produk->ukuran->nama_ukuran,
                        'satuan'      => $this->produk->ukuran->relationLoaded('satuan') && $this->produk->ukuran->satuan
                            ? [
                                'id_satuan'   => $this->produk->ukuran->satuan->id_satuan,
                                'nama_satuan' => $this->produk->ukuran->satuan->nama_satuan,
                            ]
                            : null,
                    ]
                    : null,
            ]),
        ];
    }
}
