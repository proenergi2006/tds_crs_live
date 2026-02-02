<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OngkosTruck extends Model
{
    protected $fillable = ['id_transportir', 'id_angkut_wilayah', 'created_by', 'updated_by'];

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
        return $this->hasMany(OngkosTruckDetail::class, 'id_ongkos_truck');
    }
}
