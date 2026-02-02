<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukHarga extends Model
{
    protected $primaryKey = 'id_produk_harga';
    public $timestamps = false;

    protected $fillable = [
        'periode_awal',
        'periode_akhir',
        'id_cabang',
        'id_produk',
        'harga_price_list',
        'harga_bm',
        'harga_cogs',      // ✅ baru
        'harga_margin',    // ✅ baru
        'harga_om',
        'harga_ceo',        // ✅ baru
        'catatan',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
