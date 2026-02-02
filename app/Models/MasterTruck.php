<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterTruck extends Model
{
    protected $fillable = [
        'nama_truck',    
        'nopol',
        'jenis_truck',
        'kapasitas',
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

