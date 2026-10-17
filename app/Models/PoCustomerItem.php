<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoCustomerItem extends Model
{
    use HasFactory;

    protected $table = 'po_customer_items';

    protected $fillable = [
        'po_customer_id',
        'quotation_item_id',
        'volume',
        'unit_price',
        'transport_price',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'transport_price' => 'decimal:2',
    ];

    public function finalPrice(): float
    {
        return round((float) $this->unit_price + (float) $this->transport_price, 2);
    }

    public function lineTotal(): float
    {
        return round((float) $this->volume * $this->finalPrice(), 2);
    }

    public function materialTotal(): float
    {
        return round((float) $this->volume * (float) $this->unit_price, 2);
    }

    public function transportLineTotal(): float
    {
        return round((float) $this->volume * (float) $this->transport_price, 2);
    }

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'po_customer_id', 'id_poc');
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }
}
