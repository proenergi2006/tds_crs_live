<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCustomerCreditRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_limit' => 'nullable|integer|min:0',
            'requested_top'   => 'nullable|integer|min:0',
        ];
    }
}
