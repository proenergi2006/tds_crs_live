<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'email'             => [
                'nullable',
                'email',
                Rule::unique('customers', 'email')->ignore($customer, 'id_customer'),
            ],
            'phone'             => 'nullable|string|max:50',
            'customer_type'     => 'nullable|string|max:100',
            'company_name'      => 'nullable|string|max:255',

            'customer_code'         => 'nullable|string|max:50',
            'website'               => 'nullable|string|max:255',
            'business_type'         => 'nullable|string|max:100',
            'business_type_other'   => 'nullable|string|max:255',
            'ownership_type'        => 'nullable|string|max:100',
            'ownership_type_other'  => 'nullable|string|max:255',
            'parent_company'        => 'nullable|string|max:255',
            'id_cabang'             => 'nullable|exists:cabangs,id_cabang',
            'inco_terms'            => ['nullable', new Enum(\App\Enums\CustomerIncoterm::class)],
            'inco_terms_other'      => 'nullable|string|max:255',
        ];
    }
}
