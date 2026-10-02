<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\TransportCapability;
use App\Enums\TransporterDocumentType;
use App\Enums\TransporterOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransporterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_transporter.company_name' => 'required|string|max:255',
            'data_transporter.short_name' => 'nullable|string|max:255',
            'data_transporter.ownership' => ['required', Rule::enum(TransporterOwnership::class)],
            'data_transporter.terms' => 'nullable|string|max:255',
            'data_transporter.id_cabang' => 'nullable|exists:cabangs,id_cabang',
            'data_transporter.address' => 'nullable|string',
            'data_transporter.phone' => 'nullable|string|max:50',
            'data_transporter.fax' => 'nullable|string|max:50',
            'data_transporter.transport_capability' => ['required', Rule::enum(TransportCapability::class)],
            'data_transporter.is_active' => 'boolean',
            'data_transporter.email' => 'nullable|email|max:255',
            'data_transporter.mobile_phone' => 'nullable|string|max:50',
            'data_transporter.note' => 'nullable|string',
            'data_dokumen' => 'nullable|array',
            'data_dokumen.*.document_type' => ['required_with:data_dokumen.*.file', Rule::enum(TransporterDocumentType::class)],
            'data_dokumen.*.file' => 'required_with:data_dokumen.*.document_type|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'data_dokumen.*.valid_until' => 'nullable|date',
        ];
    }
}
