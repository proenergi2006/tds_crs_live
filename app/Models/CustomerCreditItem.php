<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditItem extends Model
{
    protected $table = 'customer_credit_items';
    protected $primaryKey = 'id_item';

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
        'volume'                 => 'decimal:2',
        'existing_limit'         => 'decimal:2',
        'actual_payment'         => 'decimal:2',
        'credit_limit_request'   => 'decimal:2',
        'credit_limit_approval'  => 'decimal:2',
        'top_request'            => 'integer',
        'top_approval'           => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditSubmission::class, 'id_submission', 'id_submission');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
