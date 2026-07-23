<?php

namespace App\Models;

use App\Enums\CustomerLcrVesselQuantityCheckingMethod;
use App\Enums\CustomerLcrVesselType;
use App\Enums\CustomerLcrVesselUnloadingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CustomerLcr extends Model
{
    protected $table = 'customer_lcr';
    protected $primaryKey = 'id_lcr';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',

        /* Grup 1: Identitas & info umum */
        'site_name', 'survey_address', 'prov_survey', 'kab_survey', 'survey_date',
        'surveyor_names', 'site_business_type', 'site_business_type_other',
        'site_environment', 'site_environment_other', 'site_environment_notes',
        'competitors', 'operating_hours', 'product_volume', 'survey_notes',
        'picustomer', 'website', 'telp_survey', 'fax_survey', 'id_wilayah', 'id_wil_oa',

        /* Grup 2: Akses & rute */
        'max_truck_capacity_min', 'max_truck_capacity_max', 'access_notes',
        'route_costs', 'distance_from_depot', 'road_condition_photos',
        'min_vol_kirim', 'rute_lokasi', 'note_lokasi',

        /* Grup 3: Layout & unloading truk */
        'site_layout_photos', 'unloading_method', 'max_trucks_per_day',
        'unloading_layout_photos', 'unloading_notes',

        /* Grup 4: Penyimpanan */
        'storage_type', 'storage_type_other', 'storage_capacity',
        'storage_notes', 'storage_facility_photos',

        /* Grup 5: Verifikasi quality/quantity */
        'quality_checking_method', 'quality_checking_notes',
        'quantity_checking_method', 'quantity_checking_notes',
        'measurement_evidence_photos',

        /* Grup 6: Vessel/Jetty */
        'supports_vessel_delivery', 'vessel_type', 'vessel_cargo_capacity',
        'vessel_unloading_method', 'vessel_quantity_checking_method',
        'vessel_quantity_checking_notes', 'vessel_quality_checking_method',
        'vessel_quality_checking_notes', 'vessel_layout_photos',
        'jetty_type', 'max_loa', 'min_pbl', 'draft_lws', 'jetty_capacity_dwt',
        'jetty_permit_info', 'document_requirements',

        /* Grup 7: Foto lain & lokasi */
        'company_office_photos', 'additional_photos',
        'latitude_lokasi', 'longitude_lokasi', 'link_google_maps',

        /* Audit */
        'created_time', 'created_ip', 'created_by',
        'lastupdate_time', 'lastupdate_ip', 'lastupdate_by',
    ];

    protected $casts = [
        'id_customer' => 'integer',
        'id_wilayah'  => 'integer',
        'id_wil_oa'   => 'integer',

        'survey_date' => 'date',

        'surveyor_names' => 'array',
        'competitors'    => 'array',
        'operating_hours'=> 'array',
        'product_volume' => 'array',
        'picustomer'     => 'array',

        'max_truck_capacity_min' => 'float',
        'max_truck_capacity_max' => 'float',
        'route_costs'            => 'array',
        'road_condition_photos'  => 'array',

        'site_layout_photos'      => 'array',
        'max_trucks_per_day'      => 'integer',
        'unloading_layout_photos' => 'array',

        'storage_facility_photos' => 'array',

        'measurement_evidence_photos' => 'array',

        'supports_vessel_delivery'        => 'boolean',
        'vessel_type'                     => CustomerLcrVesselType::class,
        'vessel_unloading_method'         => CustomerLcrVesselUnloadingMethod::class,
        'vessel_quantity_checking_method' => CustomerLcrVesselQuantityCheckingMethod::class,
        'vessel_layout_photos'            => 'array',
        'max_loa'                         => 'float',
        'min_pbl'                         => 'float',
        'draft_lws'                       => 'float',
        'jetty_capacity_dwt'              => 'float',

        'company_office_photos' => 'array',
        'additional_photos'     => 'array',
        'latitude_lokasi'       => 'float',
        'longitude_lokasi'      => 'float',

        'created_time'    => 'datetime',
        'lastupdate_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    public function wilayahAngkut(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WilayahAngkut::class, 'id_wil_oa', 'id');
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\CustomerContact::class, 'id_lcr', 'id_lcr');
    }

    /**
     * Riwayat approval polymorphic (`document_approvals`, code=customer_lcr_survey).
     * morphMany (bukan morphOne) supaya re-submit setelah reject tetap
     * menyisakan riwayat siklus sebelumnya -- pola sama dengan
     * CustomerVerification::documentApprovals().
     */
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
        if ($this->latitude_lokasi === null || $this->longitude_lokasi === null) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude_lokasi,
            'lng' => (float) $this->longitude_lokasi,
        ];
    }
}
