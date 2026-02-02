<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAdminArnya extends Model
{
    protected $table = 'customer_admin_arnya';
    protected $primaryKey = 'id_arnya';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'not_yet','ov_up_07','ov_under_30','ov_under_60','ov_under_90','ov_up_90',
    ];

    protected $casts = [
        'id_arnya'   => 'integer',
        'id_customer'=> 'integer',
        'not_yet'     => 'decimal:2',
        'ov_up_07'    => 'decimal:2',
        'ov_under_30' => 'decimal:2',
        'ov_under_60' => 'decimal:2',
        'ov_under_90' => 'decimal:2',
        'ov_up_90'    => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
