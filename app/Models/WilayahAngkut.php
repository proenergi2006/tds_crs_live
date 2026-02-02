<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahAngkut extends Model
{
    protected $fillable = [
        'id_provinsi',
        'id_kabupaten',
        'destinasi',
        'is_active', // tambahkan ini
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
}
