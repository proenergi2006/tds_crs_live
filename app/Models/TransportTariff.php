<?php

namespace App\Models;

use App\Enums\TransportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportTariff extends Model
{
    protected $fillable = [
        'transporter_id',
        'transport_type',
        'transport_area_id',
        'volume_id',
        'rate',
        'note',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transport_type' => TransportType::class,
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function transportArea(): BelongsTo
    {
        return $this->belongsTo(TransportArea::class);
    }

    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }
}
