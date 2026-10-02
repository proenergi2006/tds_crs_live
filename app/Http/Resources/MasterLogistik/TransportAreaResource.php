<?php

namespace App\Http\Resources\MasterLogistik;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'provinsi' => $this->provinsi ? ['id' => $this->provinsi->id_provinsi, 'nama_provinsi' => $this->provinsi->nama_provinsi] : null,
            'kabupaten' => $this->kabupaten ? ['id' => $this->kabupaten->id_kabupaten, 'nama_kabupaten' => $this->kabupaten->nama_kabupaten] : null,
            'province' => $this->province ? ['id' => $this->province->id, 'name' => $this->province->name] : null,
            'regency' => $this->regency ? ['id' => $this->regency->id, 'name' => $this->regency->name] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
