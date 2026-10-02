<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\TransportCapability;
use App\Enums\TransporterOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransporterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:255',
            'ownership' => ['required', Rule::enum(TransporterOwnership::class)],
            'terms' => 'nullable|string|max:255',
            'id_cabang' => 'nullable|exists:cabangs,id_cabang',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'transport_capability' => ['required', Rule::enum(TransportCapability::class)],
            'is_active' => 'boolean',
            'email' => 'nullable|email|max:255',
            'mobile_phone' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ];
    }
}
