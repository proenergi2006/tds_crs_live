<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // param route default Laravel buat apiResource('approval-templates', ...) = 'approval_template' (singular, '-' jadi '_').
        $templateId = $this->route('approval_template');

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('approval_templates', 'code')->ignore($templateId, 'id_template'),
            ],
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',

            'steps'              => 'required|array|min:1',
            'steps.*.step_name'  => 'required|string|max:255',
            'steps.*.step_order' => 'required|integer|min:1|distinct',
            'steps.*.id_role'    => 'required|integer|exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'steps.*.step_order.distinct' => 'step_order tidak boleh duplikat dalam satu template.',
        ];
    }
}
