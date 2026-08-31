<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricePeriod extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'attachments' => 'array',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
