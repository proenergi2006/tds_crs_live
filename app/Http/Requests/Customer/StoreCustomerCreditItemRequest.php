<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerCreditItemRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_produk'              => 'required|integer|exists:produks,id_produk',
            'volume'                 => 'nullable|numeric',
            'unit'                   => 'nullable|string|max:50',
            'existing_limit'         => 'nullable|numeric',
            'actual_payment'         => 'nullable|numeric',
            'guarantee'              => 'nullable|string|max:255',
            'credit_limit_request'   => 'nullable|numeric',
            'credit_limit_approval'  => 'nullable|numeric',
            'top_request'            => 'nullable|integer|min:0',
            'top_approval'           => 'nullable|integer|min:0',
            'notes'                  => 'nullable|string',
        ];
    }
}
