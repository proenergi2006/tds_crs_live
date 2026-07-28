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
            // id_provinsi/id_kabupaten dilonggarkan jadi nullable (Task 8.1, opsi a) —
            // tabel referensi lama provinsis/kabupatens (13/25 baris) tidak lengkap
            // dibanding data BPS (province_id/regency_id di bawah, 38/514 baris), jadi
            // memaksa required di sini akan menolak submit valid dari dropdown BPS baru.
            'id_provinsi'       => 'nullable|exists:provinsis,id_provinsi',
            'id_kabupaten'      => 'nullable|exists:kabupatens,id_kabupaten',
            // Kolom baru berbasis kode BPS (laravel-nusa-address-full-migration).
            'province_id'       => 'nullable|string|exists:provinces,id',
            'regency_id'        => 'nullable|string|exists:regencies,id',
            'district_id'       => 'nullable|string|exists:districts,id',
            'village_id'        => 'nullable|string|exists:villages,id',
            'postal_code'       => 'nullable|string|max:20',
            'phone'             => 'nullable|string|max:50',
            'customer_type'     => 'nullable|string|max:100',
            'company_name'      => 'nullable|string|max:255',
            'company_address'   => 'nullable|string',
            'fax'               => 'nullable|string|max:50',

            'customer_code'         => 'nullable|string|max:50',
            'website'               => 'nullable|string|max:255',
            'business_type'         => 'nullable|string|max:100',
            'business_type_other'   => 'nullable|string|max:255',
            'ownership_type'        => 'nullable|string|max:100',
            'ownership_type_other'  => 'nullable|string|max:255',
            'parent_company'        => 'nullable|string|max:255',
            'customer_sub_district' => 'nullable|string|max:255',
            'customer_village'      => 'nullable|string|max:255',
            'id_cabang'             => 'nullable|exists:cabangs,id_cabang',
            'inco_terms'            => ['nullable', new Enum(\App\Enums\CustomerIncoterm::class)],
            'inco_terms_other'      => 'nullable|string|max:255',
        ];
    }
}
