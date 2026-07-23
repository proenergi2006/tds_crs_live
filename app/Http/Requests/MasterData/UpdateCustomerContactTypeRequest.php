<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerContactTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route::apiResource('customer-contact-types', ...) -> nama parameter
        // route default Laravel = singular('customer-contact-types') dengan
        // '-' -> '_' = 'customer_contact_type'.
        $id = $this->route('customer_contact_type');

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('customer_contact_types', 'code')->ignore($id),
            ],
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
