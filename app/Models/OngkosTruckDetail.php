<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OngkosTruckDetail extends Model
{
    protected $fillable = ['id_ongkos_truck', 'id_volume', 'oa'];

    public function volume()
    {
        return $this->belongsTo(Volume::class, 'id_volume', 'id_volume');
    }

    public function ongkosTruck()
    {
        return $this->belongsTo(OngkosTruck::class, 'id_ongkos_truck');
    }
}
