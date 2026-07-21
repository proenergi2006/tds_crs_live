<?php

namespace App\Models;

use App\Enums\DocumentApprovalStepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApprovalStep extends Model
{
    protected $table = 'document_approval_steps';

    protected $fillable = [
        'id_approval',
        'id_template_step',
        'step_order',
        'status',
        'actor_id',
        'acted_at',
        'decision_note',
    ];

    protected $casts = [
        'status'     => DocumentApprovalStepStatus::class,
        'step_order' => 'integer',
        'acted_at'   => 'datetime',
    ];

    public function documentApproval(): BelongsTo
    {
        return $this->belongsTo(DocumentApproval::class, 'id_approval', 'id_approval');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalTemplateStep::class, 'id_template_step', 'id_step');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'id');
    }
}
