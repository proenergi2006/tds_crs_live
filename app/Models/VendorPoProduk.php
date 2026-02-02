<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoProduk extends Model
{
    protected $table = 'vendor_pos_produks';
    protected $primaryKey = 'id_po_produk';
    public $timestamps = false;

    protected $fillable = [
        'id_po',
        'id_produk',
        'volume_po',
        'harga_tebus',
        'jumlah_harga',
        'created_time',
        'lastupdate_time',
    ];

    public function vendorPo()
    {
        return $this->belongsTo(VendorPo::class, 'id_po', 'id_po');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    
}
