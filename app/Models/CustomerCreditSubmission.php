<?php

namespace App\Models;

use App\Enums\CustomerCreditSubmissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CustomerCreditSubmission extends Model
{
    protected $table = 'customer_credit_submissions';
    protected $primaryKey = 'id_submission';

    protected $fillable = [
        'id_customer',
        'submission_type',
        'credit_limit_approval',
        'credit_limit_request',
        'top_payment',
        'financial_review_notes',
        'submitted_by',
        'submitted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submission_type'        => CustomerCreditSubmissionType::class,
        'credit_limit_approval'  => 'integer',
        'credit_limit_request'   => 'integer',
        'top_payment'            => 'integer',
        'submitted_at'           => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerCreditItem::class, 'id_submission', 'id_submission');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    /**
     * Riwayat approval polymorphic (sistem approval generik, sama mesin yang
     * dipakai CustomerVerification) -- `morphMany` (bukan `morphOne`) karena
     * `document_approvals` tidak mencegah lebih dari satu row approval per
     * submission di masa depan (mis. re-submit setelah reject).
     */
    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_submission');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }
}
