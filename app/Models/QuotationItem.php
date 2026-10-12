<?php

namespace App\Models;

use App\Enums\PriceTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $table   = 'quotation_items';
    public $timestamps = true;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'source_branch_id',
        'product_price_id',
        'unit_price',
        'quantity',
        'line_total',
        'price_tier',
        'transport_price',
        'transport_snapshot',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'quantity'    => 'decimal:2',
        'line_total'  => 'decimal:2',
        'price_tier'  => PriceTier::class,
        'transport_price'    => 'decimal:2',
        'transport_snapshot' => 'array',
    ];

    protected $hidden = ['transport_snapshot'];

    public function finalPrice(): float
    {
        return round((float) $this->unit_price + (float) $this->transport_price, 2);
    }

    public function finalTotal(): float
    {
        return round((float) $this->quantity * $this->finalPrice(), 2);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'product_id', 'id_produk');
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'source_branch_id', 'id_cabang');
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id');
    }
}
