<?php

namespace App\Http\Requests\PoCustomer;

use Illuminate\Foundation\Http\FormRequest;

class StorePoCustomerUnblockRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'        => 'nullable|string',
            'attachments'   => 'required|array|min:1',
            'attachments.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}
