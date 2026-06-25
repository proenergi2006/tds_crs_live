<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukHargaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_produk_harga'    => $this->id_produk_harga,
            'id_cabang'          => $this->id_cabang,
            'id_produk'          => $this->id_produk,
            'periode_awal'       => $this->periode_awal,
            'periode_akhir'      => $this->periode_akhir,
            'harga_price_list'   => $this->harga_price_list,
            'harga_price_list_pe'=> $this->harga_price_list_pe,
            'harga_bm'           => $this->harga_bm,
            'harga_cogs'         => $this->harga_cogs,
            'harga_margin'       => $this->harga_margin,
            'harga_om'           => $this->harga_om,
            'harga_ceo'          => $this->harga_ceo,
            'margin'             => $this->margin,
            'catatan'            => $this->catatan,
            'created_time'       => $this->created_time,
            'created_by'         => $this->created_by,
            'lastupdate_time'    => $this->lastupdate_time,
            'lastupdate_by'      => $this->lastupdate_by,
            'cabang'             => $this->whenLoaded('cabang', fn() => [
                'id_cabang'   => $this->cabang->id_cabang,
                'nama_cabang' => $this->cabang->nama_cabang,
            ]),
            'produk'             => $this->whenLoaded('produk', fn() => [
                'id_produk'   => $this->produk->id_produk,
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
