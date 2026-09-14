<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoCustomerPlan extends Model
{
    protected $table = 'po_customer_plan';
    protected $primaryKey = 'id_plan';
    public $timestamps = false;

    protected $fillable = [
        'id_poc','id_lcr','tanggal_kirim','volume_kirim','realisasi_kirim','is_urgent',
        'top_plan','actual_top_plan','pelanggan_plan','ar_notyet','ar_satu','ar_dua',
        'kredit_limit','status_plan','status_jadwal','ask_approval','catatan_reschedule',
        'is_approved','created_time','created_ip','created_by','splitted_from_plan','vol_ori_plan'
    ];

    protected $casts = [
        'tanggal_kirim' => 'date',
        'volume_kirim' => 'integer',
        'realisasi_kirim' => 'integer',
        'vol_ori_plan' => 'integer',
        'is_urgent' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_poc', 'id_poc');
    }
}
