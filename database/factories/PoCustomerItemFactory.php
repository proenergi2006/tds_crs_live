<?php

namespace Database\Factories;

use App\Models\PoCustomerItem;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoCustomerItemFactory extends Factory
{
    protected $model = PoCustomerItem::class;

    public function definition(): array
    {
        return [
            'volume' => '100.00',
            'unit_price' => '100000.00',
            'transport_price' => '0.00',
        ];
    }

    public function forQuotationItem(QuotationItem $item): static
    {
        return $this->state(fn () => [
            'quotation_item_id' => $item->id,
            'unit_price' => $item->unit_price,
            'transport_price' => $item->transport_price,
        ]);
    }
}
