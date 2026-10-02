<?php

namespace App\Models;

use App\Casts\LogisticDocumentTypeCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LogisticDocument extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'document_type',
        'file_path',
        'file_name',
        'valid_until',
        'uploaded_at',
        'uploaded_by',
    ];

    protected $casts = [
        'document_type' => LogisticDocumentTypeCast::class,
        'valid_until' => 'date',
        'uploaded_at' => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
