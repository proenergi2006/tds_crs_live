<?php

namespace App\Http\Requests\MasterData;

use App\Models\PricePeriod;
use App\Models\ProductPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(fn ($v) => $v === '' ? null : $v, $this->all()));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $current = $this->route('product_price');

            if ($validator->errors()->isNotEmpty()
                || $current?->pricePeriod?->isEnded()
                || PricePeriod::targetIsEnded($this->input('price_period_id'), $this->input('end_date'))) {
                return;
            }

            $existing = $current?->only(ProductPrice::TIER_ORDER) ?? [];
            $submitted = $this->only(ProductPrice::TIER_ORDER);

            foreach (ProductPrice::tierOrderErrors(array_merge($existing, $submitted)) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
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
            'cogs_basis'           => 'nullable|in:loco,franco',
            'cogs_material_price'  => 'nullable|numeric|min:0',
            'cogs_transport_price' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('cogs_basis') === 'franco'
                    && $this->user()?->can('product-price.manage')),
                'numeric',
                'min:0',
            ],
            'margin_material_amount' => 'nullable|numeric|min:0',
            'margin_transport_amount' => 'nullable|numeric|min:0',
            'om_price'             => 'nullable|numeric|min:0',
            'ceo_price'            => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
        ];
    }
}
