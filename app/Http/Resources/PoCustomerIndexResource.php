<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoCustomerIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customer  = $this->customer;
        $quotation = $this->quotation;

        return [
            'id_poc'           => $this->id_poc,
            'nomor_poc'        => $this->nomor_poc,
            'tanggal_poc'      => $this->tanggal_poc?->format('Y-m-d'),
            'supply_date'      => $this->supply_date?->format('Y-m-d'),
            'status_key'       => $this->status_key,
            'status_label'     => $this->status_label,
            'tipe_bayar'       => $this->tipe_bayar?->value,
            'tipe_bayar_label' => $this->tipe_bayar_label,
            'customer'         => $customer ? [
                'id_customer'   => $customer->id_customer,
                'customer_code' => $customer->customer_code,
                'company_name'  => $customer->company_name,
            ] : null,
            'quotation'        => $quotation ? [
                'id'              => $quotation->id,
                'nomor_penawaran' => $quotation->nomor_penawaran,
                'brand'           => $quotation->brand?->value,
            ] : null,
            'total_volume'     => $this->aggregatedTotalVolume(),
            'total_value'      => $this->aggregatedOrderValue(),
            'can_update'       => $request->user()?->can('update', $this->resource) ?? false,
        ];
    }
}
