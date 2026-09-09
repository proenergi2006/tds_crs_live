<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerContactRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_lcr'    => 'nullable|exists:customer_lcr,id_lcr',
            'full_name' => 'required|string|max:255',
            'position'  => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:50|required_without_all:mobile,email',
            'mobile'    => 'nullable|string|max:50|required_without_all:phone,email',
            'email'     => 'nullable|email|max:255|required_without_all:phone,mobile',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without_all'  => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
            'mobile.required_without_all' => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
            'email.required_without_all'  => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
        ];
    }
}
