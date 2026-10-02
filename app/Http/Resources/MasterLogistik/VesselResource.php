<?php

namespace App\Http\Resources\MasterLogistik;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transporter' => $this->transporter ? ['id' => $this->transporter->id, 'company_name' => $this->transporter->company_name] : null,
            'name' => $this->name,
            'max_capacity' => $this->max_capacity,
            'classification' => $this->classification,
            'length' => $this->length,
            'width' => $this->width,
            'origin' => $this->origin,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'logistic_documents_count' => (int) $this->logistic_documents_count,
            $this->mergeWhen($this->relationLoaded('logisticDocuments'), fn() => [
                'logistic_documents' => LogisticDocumentResource::collection($this->logisticDocuments),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
