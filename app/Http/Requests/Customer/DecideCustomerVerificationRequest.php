<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class DecideCustomerVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'           => ['required', 'in:approve,reject'],
            'approved_limit'   => ['required_if:action,approve', 'integer', 'min:1'],
            'approved_top'     => ['required_if:action,approve', 'integer', 'min:0'],
            'financial_review' => ['required_if:action,approve', 'string'],
            'reject_note'      => ['required_if:action,reject', 'string', 'min:1'],
        ];
    }
}
