<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKapal extends Model
{
    protected $table = 'master_kapals';

    protected $fillable = [
        'nama_kapal',
        'kapasitas_max',
        'kelas',
        'panjang',
        'lebar',
        'asal_kapal',
        'tipe_kapal',
        'id_transportir',
        'dokumen',
        'masa_berlaku',
        'lampiran',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function transportir()
    {
        return $this->belongsTo(Transportir::class, 'id_transportir');
    }
}
