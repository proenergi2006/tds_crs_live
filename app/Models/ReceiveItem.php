<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiveItem extends Model
{
    protected $table = 'receive_items';

    protected $fillable = [
        'po_id',
        'received_at',
        'nama_pic',
        'file_path',
    ];

    /**
     * Relasi “one‐to‐many” ke table receive_item_produks.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ReceiveItemProduk::class, 'receive_item_id', 'id');
    }

    /**
     * Jika ingin memanggil PO-header (opsional)
     */
    public function po()
    {
        return $this->belongsTo(VendorPo::class, 'po_id', 'id_po');
    }
}
