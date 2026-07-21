<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTemplateStep extends Model
{
    protected $table = 'approval_template_steps';
    protected $primaryKey = 'id_step';

    protected $fillable = [
        'id_template',
        'step_order',
        'step_name',
        'id_role',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'id_role'    => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ApprovalTemplate::class, 'id_template', 'id_template');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function documentApprovalSteps(): HasMany
    {
        return $this->hasMany(DocumentApprovalStep::class, 'id_template_step', 'id_step');
    }
}
