<?php

namespace App\Http\Resources\MasterLogistik;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportTariffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transporter' => $this->transporter ? ['id' => $this->transporter->id, 'company_name' => $this->transporter->company_name] : null,
            'transport_type' => $this->transport_type ? ['value' => $this->transport_type->value, 'label' => $this->transport_type->label()] : null,
            'transport_area' => $this->transportArea ? ['id' => $this->transportArea->id, 'name' => $this->transportArea->name] : null,
            'volume' => $this->volume ? ['id' => $this->volume->id, 'volume' => $this->volume->volume] : null,
            'rate' => $this->rate,
            'note' => $this->note,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
