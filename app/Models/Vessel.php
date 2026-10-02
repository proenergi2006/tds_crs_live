<?php

namespace App\Models;

use App\Models\Concerns\HasLogisticDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vessel extends Model
{
    use HasLogisticDocuments;

    protected $fillable = [
        'transporter_id',
        'name',
        'max_capacity',
        'classification',
        'length',
        'width',
        'origin',
        'type',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
