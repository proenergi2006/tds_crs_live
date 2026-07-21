<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'id_provinsi'       => 'required|exists:provinsis,id_provinsi',
            'id_kabupaten'      => 'required|exists:kabupatens,id_kabupaten',
            'postal_code'       => 'nullable|string|max:20',
            'telepon'           => 'nullable|string|max:30',
            'jenis_customer'    => 'nullable|string|max:50',
            'nama_perusahaan'   => 'nullable|string|max:255',
            'alamat_perusahaan' => 'nullable|string',
            'fax'               => 'nullable|string|max:30',
        ];
    }
}
