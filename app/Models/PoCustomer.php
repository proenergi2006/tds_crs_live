<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoCustomer extends Model
{
    protected $table = 'po_customers';
    protected $primaryKey = 'id_poc';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'id_penawaran',
        'top_poc',
        'nomor_poc',
        'tanggal_poc',
        'supply_date',
        'harga_poc',
        'volume_poc',
        'produk_poc',
        'lampiran_poc',
        'lampiran_poc_ori',
        'disposisi_poc',
        'poc_approved',
        'tgl_approved',
        'sm_result',
        'sm_pic',
        'sm_summary',
        'sm_tanggal',
        'om_result',
        'om_pic',
        'om_summary',
        'om_tanggal',
        'created_time',
        'created_ip',
        'created_by',
        'lastupdate_time',
        'lastupdate_ip',
        'lastupdate_by',
        'po_notif',
        'st_bayar_po',
        'tgl_bayar_po',
        'keterangan_bayar',
        'is_edit',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran', 'id_penawaran');
    }

    public function salesConfirmation()
{
    return $this->hasOne(\App\Models\SalesConfirmation::class, 'po_customer_id', 'id_poc');
}
}

