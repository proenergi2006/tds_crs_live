<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisProduk extends Model
{
    protected $primaryKey = 'id_jenis';
    public $timestamps = true;
    protected $fillable = [
        'nama', 'deskripsi', 'is_active',
        'created_by', 'updated_by'
    ];

    public function produks()
{
    return $this->hasMany(Produk::class, 'id_jenis');
}
}

