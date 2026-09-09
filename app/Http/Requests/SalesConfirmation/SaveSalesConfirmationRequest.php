<?php

namespace App\Http\Requests\SalesConfirmation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSalesConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credit_limit'    => 'nullable|numeric',
            'not_yet'         => 'nullable|numeric',
            'ov_up_07'        => 'nullable|numeric',
            'ov_under_30'     => 'nullable|numeric',
            'ov_under_60'     => 'nullable|numeric',
            'ov_under_90'     => 'nullable|numeric',
            'ov_up_90'        => 'nullable|numeric',

            'po_status'       => 'nullable|string|max:50',
            'po_volume'       => 'nullable|numeric',
            'po_amount'       => 'nullable|numeric',
            'reminding'       => 'nullable|string|max:255',
            'proposed_status' => 'required|in:0,1',
            'add_top'         => 'nullable|in:0,1',
            'add_cl'          => 'nullable|in:0,1',

            'type_customer'   => 'nullable|in:1,2',
            'customer_amount' => 'nullable|numeric',
            'customer_date'   => 'nullable|date',

            'customer_amount_coll.*' => 'nullable|numeric',
            'customer_date_coll.*'   => 'nullable|date',
            'item_coll.*'            => 'nullable|string|max:255',

            'lampiran_unblock'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

            'approval'        => 'required|in:0,1,2',
            'admin_summary'   => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ((int) $this->input('proposed_status') === 1 && !$this->hasFile('lampiran_unblock')) {
                $validator->errors()->add('lampiran_unblock', 'File Attachment Unblock wajib diupload saat Proposed.');
            }
        });
    }
}
