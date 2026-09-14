<?php

namespace App\Http\Requests\PoCustomer;

use Illuminate\Foundation\Http\FormRequest;

class DecidePoCustomerUnblockRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:approve,reject',
            'note'     => 'nullable|string',
        ];
    }
}
