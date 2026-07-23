<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditItem extends Model
{
    protected $table = 'customer_credit_items';

    protected $fillable = [
        'id_submission',
        'id_produk',
        'volume',
        'unit',
        'existing_limit',
        'actual_payment',
        'guarantee',
        'credit_limit_request',
        'credit_limit_approval',
        'top_request',
        'top_approval',
        'notes',
    ];

    protected $casts = [
        'volume'                 => 'decimal:4',
        'existing_limit'         => 'decimal:4',
        'actual_payment'         => 'decimal:4',
        'credit_limit_request'   => 'decimal:4',
        'credit_limit_approval'  => 'decimal:4',
        'top_request'            => 'integer',
        'top_approval'           => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditSubmission::class, 'id_submission', 'id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
