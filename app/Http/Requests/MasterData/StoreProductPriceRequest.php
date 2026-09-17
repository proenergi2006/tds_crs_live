<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(fn ($v) => $v === '' ? null : $v, $this->all()));
    }

    public function rules(): array
    {
        return [
            'price_period_id'      => 'nullable|exists:price_periods,id',
            'start_date'           => 'required_without:price_period_id|date',
            'end_date'             => 'required_without:price_period_id|date|after_or_equal:start_date',
            'branch_id'            => 'required|exists:cabangs,id_cabang',
            'product_id'           => 'required|exists:produks,id_produk',
            'price_list'           => 'nullable|numeric|min:0',
            'price_list_pe'        => 'nullable|numeric|min:0',
            'bm_price'             => 'nullable|numeric|min:0',
            'cogs_basis'           => 'required|in:loco,franco',
            'cogs_material_price'  => 'required|numeric|min:0',
            'cogs_transport_price' => 'nullable|required_if:cogs_basis,franco|numeric|min:0',
            'margin_amount'        => 'nullable|numeric|min:0',
            'om_price'             => 'nullable|numeric|min:0',
            'ceo_price'            => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
        ];
    }
}
