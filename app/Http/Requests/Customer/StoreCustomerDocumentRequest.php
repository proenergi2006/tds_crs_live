<?php

namespace App\Http\Requests\Customer;

use App\Models\CustomerDocumentType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * `authorize()` selalu true -- ownership check per-row (customer.id_user)
 * dilakukan manual di `CustomerDocumentController::store()`, bukan di sini,
 * karena butuh route param `customer` yang FormRequest ini tidak punya akses
 * sebelum route resolve.
 */
class StoreCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiresNumber = CustomerDocumentType::where('id', $this->input('id_document_type'))
            ->value('requires_number');

        return [
            'id_document_type' => 'required|integer|exists:customer_document_types,id',
            'file'             => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'document_number'  => [$requiresNumber ? 'required' : 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.required' => 'Nomor dokumen wajib diisi untuk jenis dokumen ini.',
        ];
    }
}
