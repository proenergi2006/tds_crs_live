<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\PersonnelDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_personnel.transporter_id' => 'required|exists:transporters,id',
            'data_personnel.name' => 'required|string|max:255',
            'data_personnel.photo' => 'nullable|image|max:2048',
            'data_personnel.is_active' => 'boolean',
            'data_dokumen' => 'nullable|array',
            'data_dokumen.*.document_type' => ['required_with:data_dokumen.*.file', Rule::enum(PersonnelDocumentType::class)],
            'data_dokumen.*.file' => 'required_with:data_dokumen.*.document_type|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'data_dokumen.*.valid_until' => 'nullable|date',
        ];
    }
}
