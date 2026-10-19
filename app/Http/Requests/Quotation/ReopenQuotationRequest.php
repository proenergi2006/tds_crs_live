<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class ReopenQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reopen', $this->route('quotation'));
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'max:1000'],
        ];
    }
}
