<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OngkosDetail extends Model
{
    protected $fillable = ['id_ongkos_kapal', 'id_volume', 'oa'];

    public function volume()
    {
        return $this->belongsTo(Volume::class, 'id_volume');
    }

    public function ongkosKapal()
    {
        return $this->belongsTo(OngkosKapal::class, 'id_ongkos_kapal');
    }
}