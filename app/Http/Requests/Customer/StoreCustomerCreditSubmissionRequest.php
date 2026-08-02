<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerCreditSubmissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerCreditSubmissionRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_type'        => ['required', new Enum(CustomerCreditSubmissionType::class)],
            'credit_limit_request'   => 'nullable|numeric|min:0',
            'top_request'            => 'nullable|integer|min:0',

            'items'                          => 'nullable|array',
            'items.*.id_produk'              => 'required|integer|exists:produks,id_produk',
            'items.*.volume'                 => 'nullable|numeric',
            'items.*.unit'                   => 'nullable|string|max:50',
            'items.*.existing_limit'         => 'nullable|numeric',
            'items.*.actual_payment'         => 'nullable|numeric',
            'items.*.guarantee'              => 'nullable|string|max:255',
            'items.*.credit_limit_request'   => 'nullable|numeric',
            'items.*.credit_limit_approval'  => 'nullable|numeric',
            'items.*.top_request'            => 'nullable|integer|min:0',
            'items.*.top_approval'           => 'nullable|integer|min:0',
            'items.*.notes'                  => 'nullable|string',
        ];
    }
}
