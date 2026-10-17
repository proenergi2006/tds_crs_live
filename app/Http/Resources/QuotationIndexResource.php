<?php

namespace App\Http\Resources;

use App\Support\Approval\QuotationVerifierSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canSeeInternals = QuotationVerifierSummary::canSeeInternals($request->user());

        $summary = QuotationVerifierSummary::for($this->resource, $request->user());

        if (! $canSeeInternals) {
            unset($summary['verifier_role']);
        }

        return [
            'id'                  => $this->id,
            'nomor_penawaran'     => $this->nomor_penawaran,
            'keterangan'          => $this->keterangan,
            'user_id'             => $this->user_id,
            'status'              => $this->status?->value,
            'status_label'        => $this->status?->label(),
            'brand'               => $this->brand?->value,
            'brand_label'         => $this->brand?->label(),
            ...($canSeeInternals ? ['price_tier' => $this->price_tier?->value] : []),
            ...$summary,
            'disposisi_penawaran' => $this->disposisi_penawaran,
            'disposisi_label'     => $this->disposisi_label,
            'masa_berlaku'        => $this->masa_berlaku,
            'sampai_dengan'       => $this->sampai_dengan,
            'total_volume'        => $this->total_volume,
            'dpp'                 => $this->aggregatedDpp(),
            'has_po_customer'     => (bool) $this->has_po_customer,
            'customer'            => $this->customer ? [
                'company_name' => $this->customer->company_name,
            ] : null,
            'marketing'          => $this->user ? ['name' => $this->user->name] : null,
            'items'              => $this->items->map(fn ($it) => [
                'id'                => $it->id,
                'quantity'          => $it->quantity,
                'product'            => $it->product ? [
                    'nama_produk' => $it->product->nama_produk,
                    'jenis'       => $it->product->jenis ? ['nama' => $it->product->jenis->nama] : null,
                    'ukuran'      => $it->product->ukuran ? [
                        'nama_ukuran' => $it->product->ukuran->nama_ukuran,
                    ] : null,
                ] : null,
            ])->values()->all(),
            'created_at'         => $this->created_at,
        ];
    }
}
