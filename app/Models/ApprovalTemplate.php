<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTemplate extends Model
{
    protected $table = 'approval_templates';
    protected $primaryKey = 'id_template';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalTemplateStep::class, 'id_template', 'id_template')
            ->orderBy('step_order');
    }

    public function documentApprovals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'id_template', 'id_template');
    }
}
