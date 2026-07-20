<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'provinces';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'latitude',
        'longitude',
    ];

    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class, 'province_id', 'id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'province_id', 'id');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'province_id', 'id');
    }
}
