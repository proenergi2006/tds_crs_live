<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoCustomerItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quotationItem = $this->quotationItem;
        $product       = $quotationItem?->product;
        $sourceBranch  = $quotationItem?->sourceBranch;

        return [
            'id'                => $this->id,
            'quotation_item_id' => $this->quotation_item_id,
            'product'           => $product ? [
                'id'   => $product->id_produk,
                'name' => $product->nama_produk,
                'size' => $product->ukuran?->nama_ukuran,
                'unit' => $product->ukuran?->satuan?->nama_satuan,
            ] : null,
            'source_branch'     => $sourceBranch ? [
                'id'   => $sourceBranch->id_cabang,
                'name' => $sourceBranch->nama_cabang,
            ] : null,
            'quantity_quoted'   => (float) $quotationItem?->quantity,
            'volume'            => (float) $this->volume,
            'unit_price'        => (float) $this->unit_price,
            'transport_price'   => (float) $this->transport_price,
            'final_price'       => $this->finalPrice(),
            'line_total'        => $this->lineTotal(),
        ];
    }
}
