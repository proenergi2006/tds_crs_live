<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id_role';
    public $timestamps = false;  // karena kita pakai created_time & lastupdate_time

    protected $fillable = [
        'role_name',
        'role_desc',
        'is_active',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    
}
