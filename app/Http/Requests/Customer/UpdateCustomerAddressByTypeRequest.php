<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerAddressByTypeRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_line' => ['required', 'string'],
            'province_id'  => ['nullable', 'string', 'exists:provinces,id'],
            'regency_id'   => ['nullable', 'string', 'exists:regencies,id'],
            'district_id'  => ['nullable', 'string', 'exists:districts,id'],
            'village_id'   => ['nullable', 'string', 'exists:villages,id'],
            'postal_code'  => ['nullable', 'string', 'max:20'],
        ];
    }
}
