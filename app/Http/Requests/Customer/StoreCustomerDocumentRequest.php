<?php

namespace App\Http\Requests\Customer;

use App\Models\CustomerDocumentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerDocumentRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    // customer_document_types.id di-rename id_document_type -- exists rule & lookup
    // requires_number di bawah harus ikut kolom baru, bukan kolom lama yang sudah
    // tidak ada.
    public function rules(): array
    {
        $requiresNumber = CustomerDocumentType::where('id_document_type', $this->input('id_document_type'))
            ->value('requires_number');

        return [
            'id_document_type' => 'required|integer|exists:customer_document_types,id_document_type',
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
