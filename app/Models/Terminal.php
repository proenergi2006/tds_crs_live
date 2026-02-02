<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    protected $primaryKey = 'id_terminal';
    public $timestamps = false;

    protected $fillable = [
        'nama_terminal',
        'id_cabang',
        'kategori_terminal',
        'inisial',
        'lokasi',
        'telp_terminal',
        'alamat',
        'fax',
        'pic',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }
}
