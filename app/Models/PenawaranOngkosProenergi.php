<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranOngkosProenergi extends Model
{
    protected $table = 'penawaran_ongkos_proenergi';

    protected $fillable = [
        'penawaran_id',
        'wilayah_id',
        'transportir_id',
        'volume_id',
        'jenis',        // ✅ tambah
        'ongkos',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function volume()
{
    return $this->belongsTo(\App\Models\Volume::class, 'volume_id', 'id_volume');
}

}