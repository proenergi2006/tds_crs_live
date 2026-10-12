<?php

namespace App\Models;

use Carbon\Carbon;
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

    public static function dateHasEnded($endDate): bool
    {
        return Carbon::today()->gt(Carbon::parse($endDate));
    }

    public static function targetIsEnded($pricePeriodId, $endDate): bool
    {
        if ($pricePeriodId) {
            return static::find($pricePeriodId)?->isEnded() ?? false;
        }

        return $endDate && static::dateHasEnded($endDate);
    }

    public function isEnded(): bool
    {
        return static::dateHasEnded($this->end_date);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
