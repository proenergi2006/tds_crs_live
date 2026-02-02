<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiveItemProduk extends Model
{
    protected $table = 'receive_item_produks';

    protected $fillable = [
        'receive_item_id',
        'po_produk_id',
        'id_produk', 
        'harga_tebus',
        'volume_bl',
        'volume_terima',
        'selisih',

    ];

    /**
     * Relasi ke header receive_items.
     */
    public function receiveItem()
    {
        return $this->belongsTo(ReceiveItem::class, 'receive_item_id', 'id');
    }

    /**
     * Relasi ke detail PO (VendorPoProduk).
     */
    public function poProduk()
    {
        return $this->belongsTo(VendorPoProduk::class, 'po_produk_id', 'id_po_produk');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
