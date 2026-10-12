<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportMarginRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'price_period_id'         => $this->price_period_id,
            'branch'                  => [
                'id'   => $this->cabang->id_cabang,
                'name' => $this->cabang->nama_cabang,
            ],
            'product'                 => [
                'id'          => $this->produk->id_produk,
                'nama_produk' => $this->produk->nama_produk,
                'ukuran'      => $this->produk->ukuran?->nama_ukuran,
            ],
            'cogs_transport_price'    => $this->cogs_transport_price,
            'margin_transport_amount' => $this->margin_transport_amount,
            'suggested_oat'           => $this->francoTransportPrice(),
            'is_ended'                => $this->pricePeriod->isEnded(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
            'updated_by'              => $this->updated_by,
        ];
    }
}
