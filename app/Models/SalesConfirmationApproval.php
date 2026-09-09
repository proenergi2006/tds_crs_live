<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesConfirmationApproval extends Model
{
    protected $table = 'sales_confirmation_approval';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_sales','adm_result','adm_summary','adm_result_date','adm_pic',
        'bm_result','bm_summary','bm_result_date','bm_pic',
        'om_result','om_summary','om_result_date','om_pic',
        'mgr_result','mgr_summary','mgr_result_date','mgr_pic',
        'cfo_result','cfo_summary','cfo_result_date','cfo_pic'
    ];

    protected $casts = [
        'adm_result' => 'integer',
        'bm_result' => 'integer',
        'om_result' => 'integer',
        'mgr_result' => 'integer',
        'cfo_result' => 'integer',
        'adm_result_date' => 'datetime',
        'bm_result_date' => 'datetime',
        'om_result_date' => 'datetime',
        'mgr_result_date' => 'datetime',
        'cfo_result_date' => 'datetime',
    ];

    public function salesConfirmation(): BelongsTo
    {
        return $this->belongsTo(SalesConfirmation::class, 'id_sales', 'id');
    }
}
