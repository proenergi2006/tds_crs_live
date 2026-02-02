<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranItem extends Model
{
    protected $table      = 'penawaran_items';
    protected $primaryKey = 'id_penawaran_item';
    public $timestamps    = true; // gunakan created_at dan updated_at otomatis

    protected $fillable = [
        'id_penawaran',
        'id_produk',
        'persen',
        'volume_order',
        'harga_tebus',
        'jumlah_harga',
    ];

    /******** Relasi ********/

    // PenawaranItem belongsTo Penawaran
    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran', 'id_penawaran');
    }

    // PenawaranItem belongsTo Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
