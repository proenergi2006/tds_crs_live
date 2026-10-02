<?php

namespace App\Models;

use App\Enums\CustomerLcrVesselType;
use App\Enums\CustomerLcrVesselUnloadingMethod;
use App\Enums\SiteEnvironment;
use App\Enums\StorageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CustomerLcr extends Model
{
    protected $table = 'customer_lcr';
    protected $primaryKey = 'id_lcr';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',

        'site_name', 'survey_date',
        'surveyor_names', 'site_business_type', 'site_business_type_other',
        'site_environment', 'site_environment_other', 'site_environment_notes',
        'competitors', 'operating_hours', 'product_volume', 'survey_notes',
        'id_wil_oa',

        'max_truck_capacity_min', 'max_truck_capacity_max', 'access_notes',
        'route_costs', 'distance_from_depot',
        'min_vol_kirim', 'rute_lokasi', 'note_lokasi',

        'unloading_method', 'max_trucks_per_day', 'unloading_notes',

        'storage_type', 'storage_type_other', 'storage_capacity',
        'storage_notes',

        'quality_checking_method', 'quality_checking_method_other', 'quality_checking_notes',
        'quantity_checking_method', 'quantity_checking_method_other', 'quantity_checking_notes',

        'supports_vessel_delivery', 'vessel_type', 'vessel_type_other', 'vessel_cargo_capacity',
        'vessel_unloading_method', 'vessel_unloading_method_other',
        'vessel_quantity_checking_method', 'vessel_quantity_checking_method_other', 'vessel_quantity_checking_notes',
        'vessel_quality_checking_method', 'vessel_quality_checking_method_other', 'vessel_quality_checking_notes',
        'jetty_type', 'max_loa', 'min_pbl', 'draft_lws', 'jetty_capacity_dwt',
        'jetty_permit_info', 'document_requirements',

        'latitude', 'longitude', 'google_maps_link',

        'created_at', 'created_by',
        'updated_at', 'updated_by',
    ];

    protected $casts = [
        'id_customer' => 'integer',
        'id_wil_oa'   => 'integer',

        'site_environment' => SiteEnvironment::class,

        'survey_date' => 'date',

        'product_volume' => 'array',

        'max_truck_capacity_min' => 'float',
        'max_truck_capacity_max' => 'float',
        'route_costs'            => 'array',

        'max_trucks_per_day' => 'integer',

        'storage_type' => StorageType::class,

        'quality_checking_method'  => 'array',
        'quantity_checking_method' => 'array',

        'supports_vessel_delivery'        => 'boolean',
        'vessel_type'                     => CustomerLcrVesselType::class,
        'vessel_unloading_method'         => CustomerLcrVesselUnloadingMethod::class,
        'vessel_quantity_checking_method' => 'array',
        'vessel_quality_checking_method'  => 'array',
        'max_loa'                         => 'float',
        'min_pbl'                         => 'float',
        'draft_lws'                       => 'float',
        'jetty_capacity_dwt'              => 'float',

        'latitude'  => 'float',
        'longitude' => 'float',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    public function wilayahAngkut(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TransportArea::class, 'id_wil_oa', 'id');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerContact::class, 'id_lcr', 'id_lcr');
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\CustomerDocument::class, 'id_lcr', 'id_lcr');
    }

    public function address(): HasOne
    {
        return $this->hasOne(\App\Models\CustomerAddress::class, 'id_lcr', 'id_lcr');
    }

    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_lcr');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
