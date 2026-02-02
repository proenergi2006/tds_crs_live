<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    protected $table = 'satuans';
    protected $primaryKey = 'id_satuan';

    // Kita kelola sendiri timestamps
    public $timestamps = false;

    protected $fillable = [
        'nama_satuan',
        'deskripsi',
        'is_active',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function ukurans()
{
    return $this->hasMany(Ukuran::class, 'id_satuan', 'id_satuan');
}
}
