<?php

namespace App\Http\Requests\Customer;

use App\Enums\CreditProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCustomerCreditRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_limit'  => 'nullable|integer|min:0',
            'requested_top'    => 'nullable|integer|min:0',
            'requested_qty'    => 'nullable|numeric|min:0|decimal:0,2|required_with:product_category',
            'product_category' => ['nullable', Rule::enum(CreditProductCategory::class), 'required_with:requested_qty'],
            'financial_review' => 'nullable|string',
        ];
    }
}
