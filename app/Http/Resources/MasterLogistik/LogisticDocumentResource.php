<?php

namespace App\Http\Resources\MasterLogistik;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LogisticDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'documentable_type' => $this->documentable_type,
            'documentable_id' => $this->documentable_id,
            'document_type' => ['value' => $this->document_type->value, 'label' => $this->document_type->label()],
            'file_url' => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'file_name' => $this->file_name,
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'uploaded_at' => $this->uploaded_at,
            'uploader' => $this->uploader ? ['id' => $this->uploader->id, 'name' => $this->uploader->name] : null,
            'created_at' => $this->created_at,
        ];
    }
}
