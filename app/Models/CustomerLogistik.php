<?php

namespace App\Models;

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
        'logistik_area','logistik_bisnis',
        'logistik_env','logistik_env_other',
        'logistik_storage','logistik_storage_other',
        'logistik_hour','logistik_hour_other',
        'logistik_volume','logistik_volume_other',
        'logistik_quality','logistik_quality_other',
        'logistik_truck','logistik_truck_other',
        'desc_stor_fac','desc_condition',
        'supply_shceme','specify_product','volume_per_month',
        'operational_hour_from','operational_hour_to',
        'nico',
    ];

    protected $casts = [
        'id_customer'     => 'integer',
        'logistik_env'    => 'integer',
        'logistik_storage'=> 'integer',
        'logistik_hour'   => 'integer',
        'logistik_volume' => 'integer',
        'logistik_quality'=> 'integer',
        'logistik_truck'  => 'integer',
        'supply_shceme'   => 'integer',
        'specify_product' => 'integer',
        'volume_per_month'=> 'integer',
        'nico'            => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
