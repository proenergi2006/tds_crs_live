<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `authorize()` selalu true -- ownership check per-row (customer.id_user)
 * dilakukan manual di `CustomerContactController::store()`, bukan di sini,
 * karena butuh route param `customer` yang FormRequest ini tidak punya akses
 * sebelum route resolve.
 */
class StoreCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_contact_type' => 'required|exists:customer_contact_types,id',
            'id_lcr'          => 'nullable|exists:customer_lcr,id_lcr',
            'full_name'       => 'required|string|max:255',
            'position'        => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'mobile'          => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
        ];
    }
}
