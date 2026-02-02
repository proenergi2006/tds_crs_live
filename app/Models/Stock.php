<?php
// app/Models/Stock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'po_produk_id',
        'receive_item_id',
        'produk_id',
        'volume',
        'harga_tebus',
    ];

    // Relasi ke detail PO (VendorPoProduk)
    public function poProduk()
    {
        return $this->belongsTo(VendorPoProduk::class, 'po_produk_id', 'id_po_produk');
    }

    // Relasi ke header ReceiveItem
    public function receiveItem()
    {
        return $this->belongsTo(ReceiveItem::class, 'receive_item_id', 'id');
    }

    // Relasi ke produk langsung
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'id_produk');
    }
}
