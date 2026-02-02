<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    // Primary key custom
    protected $primaryKey = 'id_cabang';

    // Tidak pakai timestamps default
    public $timestamps = false;

    // Kolom yang dapat diisi secara massal (fillable)
    protected $fillable = [
        'nama_cabang',
        'is_active',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
        'inisial_cabang',   // Kolom baru
        'inisial_segel',    // Kolom baru
        'catatan_cabang', 
        'no_po',  // Kolom baru
    ];
}
