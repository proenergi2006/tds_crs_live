<?php

namespace App\Http\Resources;

use App\Enums\SalesConfirmationStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesConfirmationIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sc            = $this->salesConfirmation;
        $quotation     = $this->quotation;
        $activeUnblock = $this->activeUnblockRequestRelation;

        $disposisi = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
        $when      = $sc?->lastupdate_time ?? $sc?->created_time ?? $this->created_time;

        $marketing = $quotation?->marketing_name
            ?? $quotation?->marketing
            ?? $quotation?->created_by
            ?? ($this->created_by ?? '-');

        return [
            'id_poc'         => $this->id_poc,
            'nomor_poc'      => $this->nomor_poc,
            'tanggal_poc'    => $this->tanggal_poc,
            'total_volume'   => $this->aggregatedTotalVolume(),
            'total_value'    => $this->aggregatedOrderValue(),
            'customer'       => [
                'customer_code' => $this->customer->customer_code ?? null,
                'company_name'  => $this->customer->company_name ?? '-',
            ],
            'quotation'      => [
                'nomor_penawaran' => $quotation->nomor_penawaran ?? '-',
            ],
            'marketing_name' => $marketing,

            'disposisi'      => $disposisi,
            'disposisi_text' => SalesConfirmationStatus::tryFrom($disposisi)?->label() ?? 'Draft',
            'disposisi_time' => $when ? Carbon::parse($when)->format('Y-m-d H:i:s') : null,

            'sc_process_state' => $this->sc_process_state?->value,
            'active_unblock_request' => $activeUnblock ? [
                'id'                 => $activeUnblock->id,
                'current_step_order' => $activeUnblock->latestDocumentApproval?->current_step_order,
            ] : null,
        ];
    }
}
