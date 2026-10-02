<?php

namespace App\Http\Requests\SalesConfirmation;

use Illuminate\Foundation\Http\FormRequest;

class SaveSalesConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_summary'         => 'nullable|string',
            'attachments'           => 'nullable|array',
            'attachments.*'         => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'removed_attachments'   => 'nullable|array',
            'removed_attachments.*' => 'string',
        ];
    }
}
