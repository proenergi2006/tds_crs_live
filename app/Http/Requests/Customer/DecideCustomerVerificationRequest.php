<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

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
            'financial_review'  => [
                'required_if:action,approve',
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($this->input('action') === 'approve' && $this->isBlankRichText((string) $value)) {
                        $fail('Financial Review wajib diisi.');
                    }
                },
            ],
            'attachments'       => ['nullable', 'array'],
            'attachments.*'     => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'reject_note'       => ['required_if:action,reject', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'financial_review.required_if' => 'Financial Review wajib diisi.',
        ];
    }

    private function isBlankRichText(string $html): bool
    {
        $text = html_entity_decode(strip_tags((string) Purifier::clean($html, 'ckeditor')),ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim($text) === '';
    }
}
