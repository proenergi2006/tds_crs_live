<?php

namespace App\Http\Requests\MasterLogistik;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVesselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transporter_id' => 'required|exists:transporters,id',
            'name' => 'required|string|max:255',
            'max_capacity' => 'nullable|integer|min:0',
            'classification' => 'nullable|string|max:255',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'origin' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
