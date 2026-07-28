<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContactType extends Model
{
    protected $table = 'customer_contact_types';
    protected $primaryKey = 'id_contact_type';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
