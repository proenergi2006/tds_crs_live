<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrDetail extends Model
{
    protected $table = 'pro_pr_detail';
    protected $primaryKey = 'id_prd';
    public $timestamps = false;

    protected $fillable = [
        'id_pr','id_plan','produk','volume','vol_potongan','vol_ket','vol_ori',
        'transport','pr_mobil','pr_top','pr_actual_top','pr_pelanggan',
        'pr_ar_notyet','pr_ar_satu','pr_ar_dua','pr_kredit_limit','pr_terminal',
        'pr_vendor','pr_harga_beli','pr_price_list','nomor_lo_pr','schedule_payment',
        'is_approved','splitted_from_pr','vol_ori_pr','splitted_from','nomor_do_accurate',
        'no_do_acurate','pr_po','no_do_syop','cabang','nomor_po_supplier',
        'id_po_supplier','id_po_receive','is_split'
    ];

    public function items()
    {
        return $this->hasMany(PrDetailItem::class, 'id_prd', 'id_prd');
    }
}
