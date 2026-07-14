<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'      => 'required|string|max:100|unique:approval_templates,code',
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',

            'steps'              => 'required|array|min:1',
            'steps.*.step_name'  => 'required|string|max:255',
            // 'distinct' dicek terhadap semua nilai steps.*.step_order yang di-flatten,
            // jadi otomatis menolak duplikat step_order dalam 1 payload template.
            'steps.*.step_order' => 'required|integer|min:1|distinct',
            'steps.*.id_role'    => 'required|integer|exists:roles,id_role',
        ];
    }

    public function messages(): array
    {
        return [
            'steps.*.step_order.distinct' => 'step_order tidak boleh duplikat dalam satu template.',
        ];
    }
}
