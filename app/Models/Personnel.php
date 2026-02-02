<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    protected $fillable = [
        'id_transportir',
        'nama',
        'photo',
        'nama_dokumen',
        'masa_berlaku',
        'lampiran',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'masa_berlaku' => 'date',
    ];

    public function transportir()
    {
        return $this->belongsTo(Transportir::class, 'id_transportir');
    }
}
