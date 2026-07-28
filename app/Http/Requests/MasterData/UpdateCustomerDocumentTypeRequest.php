<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route::apiResource('customer-document-types', ...) -> nama parameter
        // route default Laravel = singular('customer-document-types') dengan
        // '-' -> '_' = 'customer_document_type' (sama pola verifikasi seperti
        // UpdateApprovalTemplateRequest).
        $id = $this->route('customer_document_type');

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('customer_document_types', 'code')->ignore($id),
            ],
            'name'            => 'required|string|max:255',
            'is_active'       => 'boolean',
            // Optional (lihat StoreCustomerDocumentTypeRequest).
            'requires_number' => 'boolean',
        ];
    }
}
