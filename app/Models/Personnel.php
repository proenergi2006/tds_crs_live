<?php

namespace App\Models;

use App\Models\Concerns\HasLogisticDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model
{
    use HasLogisticDocuments;

    protected $fillable = [
        'transporter_id',
        'name',
        'photo',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
