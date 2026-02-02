<?php

// app/Models/SalesConfirmationApproval.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesConfirmationApproval extends Model
{
    protected $table = 'sales_confirmation_approval'; // sesuaikan
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_sales','adm_result','adm_summary','adm_result_date','adm_pic',
        'bm_result','bm_summary','bm_result_date','bm_pic',
        'om_result','om_summary','om_result_date','om_pic',
        'mgr_result','mgr_summary','mgr_result_date','mgr_pic',
        'cfo_result','cfo_summary','cfo_result_date','cfo_pic'
    ];
}
