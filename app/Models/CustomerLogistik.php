<?php

namespace App\Models;

use App\Enums\CustomerLogistikOperatingHours;
use App\Enums\QualityCheckingMethod;
use App\Enums\QuantityCheckingMethod;
use App\Enums\SiteEnvironment;
use App\Enums\StorageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLogistik extends Model
{
    protected $table = 'customer_logistik';
    protected $primaryKey = 'id_customer';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_customer',

        'site_environment', 'site_environment_other', 'site_environment_notes',
        'storage_type', 'storage_type_other', 'storage_notes',
        'operating_hours', 'operating_hours_other',
        'quality_checking_method', 'quality_checking_notes',
        'quantity_checking_method', 'quantity_checking_notes',
        'max_truck_capacity_min', 'max_truck_capacity_max',
        'supports_vessel_delivery',
        'product_notes', 'estimated_monthly_volume',
        'operational_hour_from', 'operational_hour_to',

        'created_time', 'created_ip', 'created_by',
        'lastupdate_time', 'lastupdate_ip', 'lastupdate_by',
    ];

    protected $casts = [
        'id_customer' => 'integer',

        'site_environment'         => SiteEnvironment::class,
        'storage_type'             => StorageType::class,
        'operating_hours'          => CustomerLogistikOperatingHours::class,
        'quality_checking_method'  => QualityCheckingMethod::class,
        'quantity_checking_method' => QuantityCheckingMethod::class,

        'max_truck_capacity_min' => 'float',
        'max_truck_capacity_max' => 'float',

        'supports_vessel_delivery' => 'boolean',

        'estimated_monthly_volume' => 'integer',

        'created_time'    => 'datetime',
        'lastupdate_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
