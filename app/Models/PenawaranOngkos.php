<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranOngkos extends Model
{
    protected $table = 'penawaran_ongkos';

    protected $fillable = [
        'penawaran_id',
        'wilayah_id',
        'transportir_id',
        'volume_id',
        'jenis',
        'ongkos',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function volume()
    {
        return $this->belongsTo(\App\Models\Volume::class, 'volume_id');
    }

    public function transportir()
    {
        return $this->belongsTo(\App\Models\Transporter::class, 'transportir_id');
    }

    public function wilayah()
    {
        return $this->belongsTo(\App\Models\TransportArea::class, 'wilayah_id');
    }
}