<?php

namespace App\Models;

use App\Enums\CustomerIncoterm;
use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id_customer';
    public $timestamps = false;

    protected $casts = [
        'inco_terms' => CustomerIncoterm::class,
        'customer_status' => CustomerStatus::class,
    ];

    protected $fillable = [
        // dasar
        'id_user',
        'email',

        // kolom alamat lama
        'id_provinsi',
        'id_kabupaten',

        // Kolom baru berbasis kode BPS (laravel-nusa-address-full-migration),
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'postal_code',

        'customer_type',
        'company_name',
        'company_address',
        'phone',
        'fax',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',

        // tambahan yang diminta
        'customer_code',
        'website',
        'business_type',
        'business_type_other',
        'ownership_type',
        'ownership_type_other',
        'is_link_generated',
        'update_count',
        'credit_limit',
        'credit_limit_diajukan',
        'parent_company',
        'customer_sub_district',
        'customer_village',
        'id_cabang',
        'inco_terms',
        'inco_terms_other',
        'customer_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'id_provinsi', 'id_provinsi');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten', 'id_kabupaten');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
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

    public function contacts(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerContact::class, 'id_customer', 'id_customer');
    }

    public function creditSubmissions(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerCreditSubmission::class, 'id_customer', 'id_customer');
    }

    public function latestCreditSubmission(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerCreditSubmission::class, 'id_customer', 'id_customer')
            ->latestOfMany('id_submission');
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
}
