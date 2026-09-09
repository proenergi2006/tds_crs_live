<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'corporate_detail'                 => 'required|array',
            'corporate_detail.email'           => ['nullable', 'email', 'unique:customers,email'],
            'corporate_detail.customer_type'   => 'nullable|string|max:100',
            'corporate_detail.company_name'    => 'nullable|string|max:255',
            'corporate_detail.phone'           => 'nullable|string|max:50',

            'head_office_address'              => 'required|array',
            'head_office_address.address_line' => 'nullable|string',
            'head_office_address.province_id'  => 'nullable|string|exists:provinces,id',
            'head_office_address.regency_id'   => 'nullable|string|exists:regencies,id',
            'head_office_address.district_id'  => 'nullable|string|exists:districts,id',
            'head_office_address.village_id'   => 'nullable|string|exists:villages,id',
            'head_office_address.postal_code'  => 'nullable|string|max:20',
        ];
    }
}
