<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\VesselDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVesselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_vessel.transporter_id' => 'required|exists:transporters,id',
            'data_vessel.name' => 'required|string|max:255',
            'data_vessel.max_capacity' => 'nullable|integer|min:0',
            'data_vessel.classification' => 'nullable|string|max:255',
            'data_vessel.length' => 'nullable|numeric|min:0',
            'data_vessel.width' => 'nullable|numeric|min:0',
            'data_vessel.origin' => 'nullable|string|max:255',
            'data_vessel.type' => 'nullable|string|max:255',
            'data_vessel.is_active' => 'boolean',
            'data_dokumen' => 'nullable|array',
            'data_dokumen.*.document_type' => ['required_with:data_dokumen.*.file', Rule::enum(VesselDocumentType::class)],
            'data_dokumen.*.file' => 'required_with:data_dokumen.*.document_type|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'data_dokumen.*.valid_until' => 'nullable|date',
        ];
    }
}
