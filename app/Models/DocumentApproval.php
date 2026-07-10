<?php

namespace App\Models;

use App\Enums\DocumentApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentApproval extends Model
{
    protected $table = 'document_approvals';
    protected $primaryKey = 'id_approval';

    protected $fillable = [
        'id_template',
        'approvable_type',
        'approvable_id',
        'status',
        'current_step_order',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status'              => DocumentApprovalStatus::class,
        'current_step_order'  => 'integer',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ApprovalTemplate::class, 'id_template', 'id_template');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DocumentApprovalStep::class, 'id_approval', 'id_approval')
            ->orderBy('step_order');
    }

    /**
     * Dokumen yang sedang di-approve (polymorphic): saat ini hanya
     * CustomerVerification, tapi didesain generik untuk template lain
     * (customer_lcr, quotation, po_supplier) di masa depan.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }
}
