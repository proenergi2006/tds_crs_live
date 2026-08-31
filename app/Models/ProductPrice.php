<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'price_period_id',
        'branch_id',
        'product_id',
        'price_list',
        'price_list_pe',
        'bm_price',
        'cogs_price',
        'cogs_basis',
        'margin_amount',
        'om_price',
        'ceo_price',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price_list'    => 'decimal:2',
        'price_list_pe' => 'decimal:2',
        'bm_price'      => 'decimal:2',
        'cogs_price'    => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'om_price'      => 'decimal:2',
        'ceo_price'     => 'decimal:2',
    ];

    public function pricePeriod(): BelongsTo
    {
        return $this->belongsTo(PricePeriod::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id', 'id_cabang');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'product_id', 'id_produk');
    }
}
