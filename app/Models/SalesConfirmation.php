<?php

namespace App\Models;

use App\Enums\DocumentApprovalStatus;
use App\Enums\QuotationVerifierRole;
use App\Enums\SalesConfirmationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SalesConfirmation extends Model
{
    protected $table = 'sales_confirmation';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'po_customer_id','id_customer',
        'credit_limit','not_yet','ov_up_07','ov_under_30','ov_under_60','ov_under_90','ov_up_90',
        'reminding','po_status','po_volume','po_amount',
        'proposed_status','add_top','add_cl',
        'disposisi','flag_approval','role_approved','tgl_approved',
        'lampiran_unblock','lampiran_unblock_ori',
        'type_customer','customer_amount','customer_date',
        'created_by','created_time','lastupdate_by','lastupdate_time'
    ];

    protected $casts = [
        'disposisi' => SalesConfirmationStatus::class,
        'credit_limit' => 'decimal:2',
        'not_yet' => 'decimal:2',
        'ov_up_07' => 'decimal:2',
        'ov_under_30' => 'decimal:2',
        'ov_under_60' => 'decimal:2',
        'ov_under_90' => 'decimal:2',
        'ov_up_90' => 'decimal:2',
        'po_amount' => 'decimal:2',
        'customer_amount' => 'decimal:2',
        'po_volume' => 'decimal:2',
        'proposed_status' => 'integer',
        'add_top' => 'integer',
        'add_cl' => 'integer',
        'type_customer' => 'integer',
        'flag_approval' => 'integer',
        'customer_date' => 'date',
        'tgl_approved' => 'datetime',
        'created_time' => 'datetime',
        'lastupdate_time' => 'datetime',
    ];

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'po_customer_id', 'id_poc');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    public function activeApprovalStep(): ?DocumentApprovalStep
    {
        $cycle = $this->latestDocumentApproval;

        if (! $cycle || $cycle->status !== DocumentApprovalStatus::InProgress) {
            return null;
        }

        return $cycle->steps->firstWhere('step_order', $cycle->current_step_order);
    }

    public function expectedStepOrder(): ?int
    {
        if ($this->disposisi !== SalesConfirmationStatus::InApproval) {
            return null;
        }

        $cycle = $this->latestDocumentApproval;

        if (! $cycle || $cycle->status !== DocumentApprovalStatus::InProgress || $cycle->current_step_order === null) {
            return null;
        }

        return (int) $cycle->current_step_order;
    }

    public function activeApproverRole(): ?QuotationVerifierRole
    {
        $roleId = $this->activeApprovalStep()?->id_role;

        return $roleId === null ? null : QuotationVerifierRole::tryFrom((int) $roleId);
    }

    public function statusLabel(): string
    {
        $status = SalesConfirmationStatus::tryFrom((int) $this->getRawOriginal('disposisi'));

        if ($status === null) {
            return 'Draft';
        }

        if ($status !== SalesConfirmationStatus::InApproval) {
            return $status->label();
        }

        $role = $this->activeApproverRole();

        return $role ? "Menunggu Verifikasi {$role->shortLabel()}" : $status->label();
    }

    public function isAwaitingDecision(): bool
    {
        return $this->expectedStepOrder() !== null;
    }

    public function isDecidableBy(User $user): bool
    {
        $expected = $this->expectedStepOrder();
        $step     = $this->activeApprovalStep();

        return $expected !== null
            && $step !== null
            && (int) $step->step_order === $expected
            && $user->can('sales-confirmation.approve')
            && $user->hasRole((int) $step->id_role);
    }
}
