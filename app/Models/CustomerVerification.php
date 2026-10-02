<?php

namespace App\Models;

use App\Enums\CreditProductCategory;
use App\Enums\CustomerVerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CustomerVerification extends Model
{
    protected $table = 'customer_verifications';

    protected $primaryKey = 'id_verification';

    protected $fillable = [
        'id_customer',
        'status',
        'is_scheduled',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'reviewed_by',
        'reject_note',
        'requested_limit_snapshot',
        'requested_top_snapshot',
        'requested_qty_snapshot',
        'product_category_snapshot',
        'approved_limit',
        'approved_top',
        'financial_review_snapshot',
        'notes',
        'finance_attachments',
    ];

    protected $casts = [
        'status' => CustomerVerificationStatus::class,
        'is_scheduled' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'requested_limit_snapshot' => 'integer',
        'requested_top_snapshot' => 'integer',
        'requested_qty_snapshot' => 'decimal:2',
        'product_category_snapshot' => CreditProductCategory::class,
        'approved_limit' => 'integer',
        'approved_top' => 'integer',
        'finance_attachments' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by', 'id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by', 'id');
    }

    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_verification');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    public function formatFinanceAttachments(): array
    {
        return \App\Support\PublicAttachmentFormatter::format($this->finance_attachments);
    }
}
