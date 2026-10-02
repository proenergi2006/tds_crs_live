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
            'action'            => ['required', 'in:approve,reject'],
            'approved_limit'    => ['required_if:action,approve', 'integer', 'min:1'],
            'approved_top'      => ['required_if:action,approve', 'integer', 'min:0'],
            'notes'             => ['nullable', 'string'],
            'attachments'       => ['nullable', 'array'],
            'attachments.*'     => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'reject_note'       => ['required_if:action,reject', 'string', 'min:1'],
        ];
    }
}
