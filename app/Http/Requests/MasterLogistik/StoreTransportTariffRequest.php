<?php

namespace App\Http\Requests\MasterLogistik;

use App\Enums\TransportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportTariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transporter_id' => 'required|exists:transporters,id',
            'transport_type' => ['required', Rule::enum(TransportType::class)],
            'transport_area_id' => 'required|exists:transport_areas,id',
            'volume_id' => 'required|exists:volumes,id',
            'rate' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
