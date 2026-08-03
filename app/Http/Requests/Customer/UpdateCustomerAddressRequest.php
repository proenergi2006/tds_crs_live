<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerAddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerAddressRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_type' => ['required', new Enum(CustomerAddressType::class)],
            'address_line' => 'required|string',
            'province_id'  => 'nullable|string|exists:provinces,id',
            'regency_id'   => 'nullable|string|exists:regencies,id',
            'district_id'  => 'nullable|string|exists:districts,id',
            'village_id'   => 'nullable|string|exists:villages,id',
            'postal_code'  => 'nullable|string|max:10',
            'is_primary'   => 'boolean',
        ];
    }
}
