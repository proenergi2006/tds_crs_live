<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerPaymentTermBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerPaymentRequest extends FormRequest
{
    // authorize() selalu true, ownership dicek di controller. Konvensinya ada di standards/backend.md.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule'       => ['nullable', 'string', 'max:50'],
            'schedule_other' => ['nullable', 'string', 'max:255'],
            'method'         => ['nullable', 'string', 'max:50'],
            'method_other'   => ['nullable', 'string', 'max:255'],
            'invoice_tax'    => ['nullable', 'boolean'],
            'note'           => ['nullable', 'string'],
            'bank_name'      => ['nullable', 'string', 'max:150'],
            'currency'       => ['nullable', 'string', 'max:10'],
            'bank_address'   => ['nullable', 'string'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'has_credit'     => ['nullable', 'boolean'],
            'creditor_name'  => ['nullable', 'string', 'max:150'],
            'term'           => ['nullable', new Enum(CustomerPaymentTerm::class)],
            'term_days'      => ['nullable', 'integer', 'min:0'],
            'term_basis'     => ['nullable', new Enum(CustomerPaymentTermBasis::class)],
        ];
    }
}
