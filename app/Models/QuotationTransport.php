<?php

namespace App\Models;

use App\Enums\TransportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationTransport extends Model
{
    protected $table = 'quotation_transports';

    protected $fillable = [
        'quotation_id',
        'transport_type',
        'transport_tariff_id',
    ];

    protected $casts = [
        'transport_type' => TransportType::class,
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function transportTariff(): BelongsTo
    {
        return $this->belongsTo(TransportTariff::class);
    }
}
