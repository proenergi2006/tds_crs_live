<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $primaryKey = 'id_produk';
    public $timestamps = false;

    protected $fillable = [
        'nama_produk',
        'merk_dagang',
        'deskripsi',
        'id_ukuran',
        'id_jenis',
        'is_active',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    // relasi ke Ukuran
    public function ukuran()
    {
        return $this->belongsTo(Ukuran::class, 'id_ukuran', 'id_ukuran');
    }
    public function jenis()
{
    return $this->belongsTo(JenisProduk::class, 'id_jenis');
}
}
