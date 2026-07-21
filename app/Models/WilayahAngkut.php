<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahAngkut extends Model
{
    protected $fillable = [
        'id_provinsi',
        'id_kabupaten',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'destinasi',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'id_provinsi');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
    }
}
