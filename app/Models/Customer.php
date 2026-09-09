<?php

namespace App\Models;

use App\Enums\CustomerAddressType;
use App\Enums\CustomerIncoterm;
use App\Enums\CustomerStatus;
use App\Enums\CustomerVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id_customer';
    public $timestamps = false;

    public const REVERIFICATION_INTERVAL_MONTHS = 6;

    protected $casts = [
        'inco_terms' => CustomerIncoterm::class,
        'customer_status' => CustomerStatus::class,
        'token_expired_at' => 'datetime',
    ];

    protected $fillable = [
        'id_user',
        'email',

        'customer_type',
        'company_name',
        'phone',
        'fax',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',

        'customer_code',
        'website',
        'business_type',
        'business_type_other',
        'ownership_type',
        'ownership_type_other',
        'update_count',
        'parent_company',
        'id_cabang',
        'inco_terms',
        'inco_terms_other',
        'customer_status',
        'onboarding_token',
        'token_expired_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function lcr(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerLcr::class, 'id_customer', 'id_customer');
    }

    public function penawarans(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'id_customer', 'id_customer');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerDocument::class, 'id_customer', 'id_customer');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerAddress::class, 'id_customer', 'id_customer');
    }

    public function headOfficeAddress(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerAddress::class, 'id_customer', 'id_customer')
            ->where('address_type', CustomerAddressType::HeadOffice->value);
    }

    public function scopeWithHeadOfficeAddressLine(Builder $query): Builder
    {
        return $query->addSelect(['company_address' => CustomerAddress::query()
            ->select('address_line')
            ->whereColumn('customer_addresses.id_customer', 'customers.id_customer')
            ->where('address_type', CustomerAddressType::HeadOffice->value)
            ->limit(1)]);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerContact::class, 'id_customer', 'id_customer');
    }

    public function creditRequest(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerCreditRequest::class, 'id_customer', 'id_customer');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerStatusHistory::class, 'id_customer', 'id_customer');
    }

    public function verifications()
    {
        return $this->hasMany(\App\Models\CustomerVerification::class, 'id_customer', 'id_customer');
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerVerification::class, 'id_customer', 'id_customer')
            ->latestOfMany('id_verification');
    }

    public function latestApprovedVerification(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerVerification::class, 'id_customer', 'id_customer')
            ->ofMany(
                ['reviewed_at' => 'max'],
                fn (Builder $query) => $query->where('status', CustomerVerificationStatus::Approved)
            );
    }

    public function logistik(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerLogistik::class, 'id_customer', 'id_customer');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerPayment::class, 'id_customer', 'id_customer');
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->latestApprovedVerification !== null;
    }

    public function getNeedsReverificationAttribute(): bool
    {
        $cycle = $this->latestApprovedVerification;

        return $cycle?->reviewed_at !== null
            && $cycle->reviewed_at->lt(now()->subMonths(self::REVERIFICATION_INTERVAL_MONTHS));
    }

    public function getCurrentCreditLimitAttribute(): ?int
    {
        return $this->latestApprovedVerification?->approved_limit;
    }

    public function isUnderReview(): bool
    {
        return $this->latestVerification?->status === CustomerVerificationStatus::InReview;
    }
}
