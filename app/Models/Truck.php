<?php

namespace App\Models;

use App\Models\Concerns\HasLogisticDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Truck extends Model
{
    use HasLogisticDocuments;

    protected $fillable = [
        'transporter_id',
        'license_plate',
        'type',
        'max_capacity',
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
