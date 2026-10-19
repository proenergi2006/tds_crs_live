<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoCustomerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customer  = $this->customer;
        $quotation = $this->quotation;
        $sc        = $this->salesConfirmation;

        return [
            'id_poc'             => $this->id_poc,
            'id_customer'        => $this->id_customer,
            'quotation_id'       => $this->quotation_id,
            'nomor_poc'          => $this->nomor_poc,
            'tanggal_poc'        => $this->tanggal_poc?->format('Y-m-d'),
            'supply_date'        => $this->supply_date?->format('Y-m-d'),
            'tipe_bayar'         => $this->tipe_bayar?->value,
            'tipe_bayar_label'   => $this->tipe_bayar_label,
            'termin_hari'        => $this->termin_hari,
            'lampiran_poc'       => $this->lampiran_poc,
            'lampiran_poc_ori'   => $this->lampiran_poc_ori,
            'status_key'         => $this->status_key,
            'status_label'       => $this->status_label,
            'is_locked'          => $this->is_locked,
            'sc_process_state'   => $this->sc_process_state?->value,
            'created_by'         => $this->created_by,
            'created_time'       => $this->created_time?->format('Y-m-d H:i:s'),
            'customer'           => $customer ? [
                'id_customer'   => $customer->id_customer,
                'customer_code' => $customer->customer_code,
                'company_name'  => $customer->company_name,
            ] : null,
            'quotation'          => $quotation ? [
                'id'              => $quotation->id,
                'nomor_penawaran' => $quotation->nomor_penawaran,
                'brand'           => $quotation->brand?->value,
                'masa_berlaku'    => $quotation->masa_berlaku,
                'sampai_dengan'   => $quotation->sampai_dengan,
                'transport_basis' => $quotation->transport_basis?->value,
            ] : null,
            'items'              => PoCustomerItemResource::collection($this->items->sortBy('id')->values()),
            'totals'             => $this->orderTotals(),
            'sales_confirmation' => $sc ? [
                'id'              => $sc->id,
                'disposisi'       => $sc->disposisi?->value,
                'disposisi_label' => $sc->disposisi?->label(),
            ] : null,
            'can_update'         => $request->user()?->can('update', $this->resource) ?? false,
        ];
    }
}
