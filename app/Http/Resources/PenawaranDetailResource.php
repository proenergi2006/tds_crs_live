<?php

namespace App\Http\Resources;

use App\Support\Approval\PenawaranApprovalStepsBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenawaranDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $brand = $request->route('brand');

        $data = $this->resource->makeHidden('documentApprovals')->toArray();

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

        $data['cabang'] = $this->cabang ? [
            'nama_cabang' => $this->cabang->nama_cabang,
        ] : null;

        $data['items'] = $this->items->map(function ($item) use ($brand) {
            $productPrice = $item->productPrice;

            return [
                'id_penawaran_item' => $item->id_penawaran_item,
                'id_produk'         => $item->id_produk,
                'volume_order'      => $item->volume_order,
                'persen'            => $item->persen,
                'harga_tebus'       => $item->harga_tebus,
                'jumlah_harga'      => $item->jumlah_harga,
                'source_branch_id'  => $item->source_branch_id,
                'product_price_id'  => $item->product_price_id,
                'price_list'        => $productPrice?->priceListForBrand($brand),
                'cogs_price'        => $productPrice?->cogs_price,
                'cogs_basis'        => $productPrice?->cogs_basis,
                'source_branch'     => $item->sourceCabang
                    ? ['id' => $item->sourceCabang->id_cabang, 'nama_cabang' => $item->sourceCabang->nama_cabang]
                    : null,
                'produk'           => $item->produk ? [
                    'id_produk'   => $item->produk->id_produk,
                    'nama_produk' => $item->produk->nama_produk,
                    'jenis'       => $item->produk->jenis ? ['nama' => $item->produk->jenis->nama] : null,
                    'ukuran'      => $item->produk->ukuran ? [
                        'nama_ukuran' => $item->produk->ukuran->nama_ukuran,
                        'satuan'      => $item->produk->ukuran->satuan ? ['nama_satuan' => $item->produk->ukuran->satuan->nama_satuan] : null,
                    ] : null,
                ] : null,
            ];
        })->all();

        $data['approval_attempts'] = app(PenawaranApprovalStepsBuilder::class)->buildAttempts($this->resource);

        return $data;
    }
}
