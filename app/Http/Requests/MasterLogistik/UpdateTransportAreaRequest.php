<?php

namespace App\Http\Requests\MasterLogistik;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'id_provinsi' => 'nullable|exists:provinsis,id_provinsi',
            'id_kabupaten' => 'nullable|exists:kabupatens,id_kabupaten',
            'province_id' => 'nullable|string|exists:provinces,id',
            'regency_id' => 'nullable|string|exists:regencies,id',
        ];
    }
}
