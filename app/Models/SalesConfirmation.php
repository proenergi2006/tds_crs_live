<?php

// app/Models/SalesConfirmation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesConfirmation extends Model
{
    protected $table = 'sales_confirmation';   // sesuaikan
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'po_customer_id','id_customer',
        'credit_limit','not_yet','ov_up_07','ov_under_30','ov_under_60','ov_under_90','ov_up_90',
        'reminding','po_status','po_volume','po_amount',
        'proposed_status','add_top','add_cl',
        'disposisi','flag_approval','role_approved','tgl_approved',
        'lampiran_unblock','lampiran_unblock_ori',
        'type_customer','customer_amount','customer_date',
        'created_by','created_time','lastupdate_by','lastupdate_time'
    ];
}
