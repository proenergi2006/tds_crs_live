<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProPr extends Model
{
    protected $table = 'pro_pr';
    protected $primaryKey = 'id_pr';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // boleh pakai guarded kosong biar fleksibel
    protected $guarded = [];
}
