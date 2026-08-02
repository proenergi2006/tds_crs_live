<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerCreditSubmissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerCreditSubmissionRequest extends FormRequest
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
        ];
    }
}
