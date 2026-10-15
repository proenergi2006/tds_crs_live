<?php

namespace App\Models;

use App\Enums\DocumentApprovalStatus;
use App\Enums\PoCustomerPaymentType;
use App\Enums\PoCustomerScProcessState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoCustomer extends Model
{
    protected $table = 'po_customers';
    protected $primaryKey = 'id_poc';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'quotation_id',
        'tipe_bayar',
        'termin_hari',
        'nomor_poc',
        'tanggal_poc',
        'supply_date',
        'harga_poc',
        'volume_poc',
        'produk_poc',
        'lampiran_poc',
        'lampiran_poc_ori',
        'created_time',
        'created_ip',
        'created_by',
        'lastupdate_time',
        'lastupdate_ip',
        'lastupdate_by',
        'po_notif',
        'st_bayar_po',
        'tgl_bayar_po',
        'keterangan_bayar',
        'is_edit',
        'sc_process_state',
    ];

    protected $casts = [
        'tanggal_poc' => 'date:Y-m-d',
        'supply_date' => 'date:Y-m-d',
        'harga_poc' => 'decimal:4',
        'volume_poc' => 'integer',
        'created_time' => 'datetime',
        'lastupdate_time' => 'datetime',
        'sc_process_state' => PoCustomerScProcessState::class,
        'tipe_bayar' => PoCustomerPaymentType::class,
        'termin_hari' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->quotation !== null && (int) $this->quotation->user_id === (int) $user->id;
    }

    public function isWritableBy(User $user): bool
    {
        $brand = $this->quotation?->brand;

        return $brand !== null
            && $user->can($brand->managePermission())
            && ($user->can($brand->viewAnyPermission()) || $this->isOwnedBy($user));
    }

    public function isReadableBy(User $user): bool
    {
        $brand = $this->quotation?->brand;

        return ($brand !== null && $user->can($brand->viewAnyPermission()))
            || static::readsAllPosAs($user)
            || $this->isOwnedBy($user);
    }

    public static function readsAllPosAs(User $user): bool
    {
        return $user->can('sales-confirmation.manage') && $user->cant('sales-confirmation.approve');
    }

    public function scopeSalesConfirmationVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('sales-confirmation.view')) {
            return $query;
        }

        if ($user->can('sales-confirmation.approve')) {
            return $query->whereHas(
                'salesConfirmation.documentApprovals.steps',
                fn (Builder $steps) => $steps->whereIn('id_role', $user->roles->pluck('id')->all())
            );
        }

        if ($user->can('sales-confirmation.manage')) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function isSalesConfirmationVisibleTo(User $user): bool
    {
        return static::whereKey($this->getKey())->salesConfirmationVisibleTo($user)->exists();
    }

    public function salesConfirmation(): HasOne
    {
        return $this->hasOne(SalesConfirmation::class, 'po_customer_id', 'id_poc');
    }

    public function unblockRequests(): HasMany
    {
        return $this->hasMany(PoCustomerUnblockRequest::class, 'id_poc', 'id_poc');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(PoCustomerPlan::class, 'id_poc', 'id_poc');
    }

    public function activeUnblockRequest():?PoCustomerUnblockRequest
    {
        return $this->unblockRequests()->where('status', DocumentApprovalStatus::InProgress)->latest('id')->first();
    }

    public function approvedUnblockRequest(): ?PoCustomerUnblockRequest
    {
        return $this->unblockRequests()->where('status', DocumentApprovalStatus::Approved)->first();
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->sc_process_state === PoCustomerScProcessState::Cleared
            || ($this->sc_process_state === PoCustomerScProcessState::Blocked && $this->activeUnblockRequest() !== null)
            || $this->salesConfirmation !== null;
    }

    public function getStatusKeyAttribute(): string
    {
        $sc = $this->salesConfirmation;

        return match (true) {
            $sc !== null && (int) $sc->getRawOriginal('disposisi') === 4 => 'done',
            $sc !== null                                                 => 'sc_in_progress',
            $this->sc_process_state === PoCustomerScProcessState::Blocked => 'blocked',
            $this->sc_process_state === PoCustomerScProcessState::Cleared => 'awaiting_sc',
            default                                                      => 'awaiting_process',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'awaiting_process' => 'Menunggu Proses SC',
            'blocked'          => 'Block SC',
            'awaiting_sc'      => 'Menunggu Sales Confirmation',
            'sc_in_progress'   => 'Sales Confirmation Diproses',
            'done'             => 'Selesai',
        };
    }

    public function getTipeBayarLabelAttribute(): ?string
    {
        return $this->tipe_bayar?->label();
    }
}
