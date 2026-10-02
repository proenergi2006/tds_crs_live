<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\TruckDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTruckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_truck.transporter_id' => 'required|exists:transporters,id',
            'data_truck.license_plate' => 'required|string|max:255|unique:trucks,license_plate',
            'data_truck.type' => 'required|string|max:255',
            'data_truck.max_capacity' => 'required|integer|min:0',
            'data_truck.name' => 'nullable|string|max:255',
            'data_truck.is_active' => 'boolean',
            'data_dokumen' => 'nullable|array',
            'data_dokumen.*.document_type' => ['required_with:data_dokumen.*.file', Rule::enum(TruckDocumentType::class)],
            'data_dokumen.*.file' => 'required_with:data_dokumen.*.document_type|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'data_dokumen.*.valid_until' => 'nullable|date',
        ];
    }
}
