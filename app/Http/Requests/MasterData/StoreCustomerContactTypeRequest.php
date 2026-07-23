<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerContactTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'      => 'required|string|max:100|unique:customer_contact_types,code',
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
