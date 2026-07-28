<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerAddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * `authorize()` selalu true -- ownership check per-row (customer.id_user)
 * dilakukan manual di `CustomerAddressController::store()`, bukan di sini,
 * karena butuh route param `customer` yang FormRequest ini tidak punya akses
 * sebelum route resolve.
 */
class StoreCustomerAddressRequest extends FormRequest
{
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
