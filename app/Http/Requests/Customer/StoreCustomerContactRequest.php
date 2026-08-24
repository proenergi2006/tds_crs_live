<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerContactRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_lcr'    => 'nullable|exists:customer_lcr,id_lcr',
            'full_name' => 'required|string|max:255',
            'position'  => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'mobile'    => 'nullable|string|max:50',
            'email'     => 'nullable|email|max:255',
        ];
    }
}
