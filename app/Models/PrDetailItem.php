<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrDetailItem extends Model
{
    protected $table = 'pro_pr_detail_items';
    protected $primaryKey = 'id_prdi';
    public $timestamps = false;

    protected $fillable = [
        'id_prd','id_penawaran_item','produk','ukuran',
        'volume_item','harga_item','subtotal'
    ];

    public function prDetail()
    {
        return $this->belongsTo(PrDetail::class, 'id_prd', 'id_prd');
    }
}
