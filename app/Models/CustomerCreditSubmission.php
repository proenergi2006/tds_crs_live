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

    protected $fillable = [
        'id_customer',
        'submission_type',
        'top_payment',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submission_type' => CustomerCreditSubmissionType::class,
        'top_payment'      => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerCreditItem::class, 'id_submission', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Riwayat approval polymorphic (sistem approval generik, sama mesin yang
     * dipakai CustomerVerification) -- `morphMany` (bukan `morphOne`) karena
     * `document_approvals` tidak mencegah lebih dari satu row approval per
     * submission di masa depan (mis. re-submit setelah reject).
     */
    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }
}
