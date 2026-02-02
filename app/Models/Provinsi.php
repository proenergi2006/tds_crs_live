<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $primaryKey = 'id_provinsi';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'nama_provinsi',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function kabupatens()
    {
        return $this->hasMany(Kabupaten::class, 'id_provinsi', 'id_provinsi');
    }
}
