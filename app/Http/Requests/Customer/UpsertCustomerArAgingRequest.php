<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCustomerArAgingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outstanding_current' => ['required', 'numeric', 'min:0'],
            'overdue_1_30'        => ['required', 'numeric', 'min:0'],
            'overdue_31_60'       => ['required', 'numeric', 'min:0'],
            'overdue_61_90'       => ['required', 'numeric', 'min:0'],
            'overdue_90_plus'     => ['required', 'numeric', 'min:0'],
        ];
    }
}
