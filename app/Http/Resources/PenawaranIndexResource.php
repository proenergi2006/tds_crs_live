<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenawaranIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_penawaran'        => $this->id_penawaran,
            'nomor_penawaran'     => $this->nomor_penawaran,
            'user_id'             => $this->user_id,
            'disposisi_penawaran' => $this->disposisi_penawaran,
            'masa_berlaku'        => $this->masa_berlaku,
            'sampai_dengan'       => $this->sampai_dengan,
            'total_volume'        => $this->total_volume,
            'customer'            => $this->customer ? [
                'company_name' => $this->customer->company_name,
            ] : null,
            'marketing'          => $this->user ? ['name' => $this->user->name] : null,
            'items'              => $this->items->map(fn ($it) => [
                'id_penawaran_item' => $it->id_penawaran_item,
                'volume_order'      => $it->volume_order,
                'produk'            => $it->produk ? [
                    'nama_produk' => $it->produk->nama_produk,
                    'jenis'       => $it->produk->jenis ? ['nama' => $it->produk->jenis->nama] : null,
                    'ukuran'      => $it->produk->ukuran ? [
                        'nama_ukuran' => $it->produk->ukuran->nama_ukuran,
                    ] : null,
                ] : null,
            ])->values()->all(),
            'created_at'         => $this->created_at,
        ];
    }
}
