<?php

namespace App\Models;

use App\Enums\DocumentApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PoCustomerUnblockRequest extends Model
{
    protected $table = 'po_customer_unblock_requests';

    protected $fillable = [
        'id_poc',
        'reason',
        'attachments',
        'status',
        'requested_by',
    ];

    protected $casts = [
        'status' => DocumentApprovalStatus::class,
        'attachments' => 'array',
    ];

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_poc', 'id_poc');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }
}
