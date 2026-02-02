<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OngkosKapal extends Model
{
    protected $fillable = [
        'id_transportir',
        'id_angkut_wilayah',
        'catatan',
        'created_by',
        'updated_by'
    ];

    public function transportir()
    {
        return $this->belongsTo(Transportir::class, 'id_transportir');
    }

    public function angkutWilayah()
    {
        return $this->belongsTo(WilayahAngkut::class, 'id_angkut_wilayah');
    }

    public function details()
    {
        return $this->hasMany(OngkosDetail::class, 'id_ongkos_kapal');
    }
}