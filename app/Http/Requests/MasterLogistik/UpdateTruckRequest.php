<?php

namespace App\Http\Requests\MasterLogistik;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transporter_id' => 'required|exists:transporters,id',
            'license_plate' => ['required', 'string', 'max:255', Rule::unique('trucks', 'license_plate')->ignore($this->route('truck'))],
            'type' => 'required|string|max:255',
            'max_capacity' => 'required|integer|min:0',
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
