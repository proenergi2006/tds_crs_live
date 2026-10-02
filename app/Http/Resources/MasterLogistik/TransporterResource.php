<?php

namespace App\Http\Resources\MasterLogistik;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransporterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'short_name' => $this->short_name,
            'ownership' => $this->ownership ? ['value' => $this->ownership->value, 'label' => $this->ownership->label()] : null,
            'terms' => $this->terms,
            'cabang' => $this->id_cabang ? ['id' => $this->cabang->id_cabang, 'nama_cabang' => $this->cabang->nama_cabang] : null,
            'address' => $this->address,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'transport_capability' => $this->transport_capability ? ['value' => $this->transport_capability->value, 'label' => $this->transport_capability->label()] : null,
            'is_active' => $this->is_active,
            'email' => $this->email,
            'mobile_phone' => $this->mobile_phone,
            'note' => $this->note,
            $this->mergeWhen((bool) $request->user()?->can('logistik.master.manage'), fn () => [
                'personnels_count' => (int) $this->personnels_count,
                'vessels_count' => (int) $this->vessels_count,
                'trucks_count' => (int) $this->trucks_count,
            ]),
            $this->mergeWhen($this->relationLoaded('logisticDocuments'), fn () => [
                'logistic_documents' => LogisticDocumentResource::collection($this->logisticDocuments),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
