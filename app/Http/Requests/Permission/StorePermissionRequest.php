<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:150|regex:/^[a-z0-9-]+(\.[a-z0-9-]+)+$/|unique:permissions,name,NULL,id,guard_name,web',
            'module'      => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Format name harus mengikuti pola module.action dengan huruf kecil, angka, dan tanda hubung (mis. "produk-harga.create").',
        ];
    }
}
