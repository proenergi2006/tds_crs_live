<?php

namespace App\Http\Requests\Customer;

use App\Models\CustomerDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerDocumentRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    // requires_number sudah di-drop dari master, wajib-nomor sekarang hardcoded per code.
    public function rules(): array
    {
        $code = $this->filled('id_document_type')
            ? CustomerDocumentType::where('id_document_type', $this->input('id_document_type'))->value('code')
            : null;
        $requiresNumber = in_array($code, ['nib', 'npwp'], true);

        return [
            'id_document_type' => [
                'nullable', 'integer', 'exists:customer_document_types,id_document_type',
                Rule::requiredIf(!$this->filled('document_name')),
                Rule::prohibitedIf($this->filled('document_name')),
            ],
            'document_name' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(!$this->filled('id_document_type')),
                Rule::prohibitedIf($this->filled('id_document_type')),
            ],
            'file'             => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'document_number'  => [$requiresNumber ? 'required' : 'nullable', 'string', 'max:255'],
            'id_lcr'           => 'nullable|integer|exists:customer_lcr,id_lcr',
            'notes'            => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.required' => 'Nomor dokumen wajib diisi untuk jenis dokumen ini.',
        ];
    }
}
