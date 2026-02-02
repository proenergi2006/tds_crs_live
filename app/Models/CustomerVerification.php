<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVerification extends Model
{
    protected $table = 'customer_verifications';
    protected $primaryKey = 'id_verification';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'token_verification',
        'is_evaluated',
        'is_reviewed',
        'is_active',

        'legal_data',
        'legal_summary',
        'legal_result',
        'legal_tgl_proses',
        'legal_pic',

        'finance_data',
        'finance_summary',
        'finance_result',
        'finance_tgl_proses',
        'finance_pic',

        'logistik_data',
        'logistik_summary',
        'logistik_result',
        'logistik_tgl_proses',
        'logistik_pic',

        'sm_summary',
        'sm_result',
        'sm_tgl_proses',
        'sm_pic',

        'om_summary',
        'om_result',
        'om_tgl_proses',
        'om_pic',

        'cfo_summary',
        'cfo_result',
        'cfo_tgl_proses',
        'cfo_pic',

        'ceo_summary',
        'ceo_result',
        'ceo_tgl_proses',
        'ceo_pic',

        'disposisi_result',
        'is_approved',
        'role_approve',
        'tanggal_approved',
        'jenis_datanya',
        'finance_data_kyc',
    ];

    protected $casts = [
        'is_evaluated' => 'boolean',
        'is_reviewed'  => 'boolean',
        'is_active'    => 'boolean',
        'is_approved'  => 'boolean',

        'legal_result'    => 'integer',
        'finance_result'  => 'integer',
        'logistik_result' => 'integer',
        'sm_result'       => 'integer',
        'om_result'       => 'integer',
        'cfo_result'      => 'integer',
        'ceo_result'      => 'integer',
        'disposisi_result'=> 'integer',
        'role_approve'    => 'integer',
        'jenis_datanya'   => 'integer',

        'legal_tgl_proses'    => 'datetime',
        'finance_tgl_proses'  => 'datetime',
        'logistik_tgl_proses' => 'datetime',
        'sm_tgl_proses'       => 'datetime',
        'om_tgl_proses'       => 'datetime',
        'cfo_tgl_proses'      => 'datetime',
        'ceo_tgl_proses'      => 'datetime',
        'tanggal_approved'    => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    
}
