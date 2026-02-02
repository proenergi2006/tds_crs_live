<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ukuran extends Model
{
    protected $primaryKey = 'id_ukuran';
    public $timestamps = false;

    protected $fillable = [
        'nama_ukuran',
        'id_satuan',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan', 'id_satuan');
    }
}
