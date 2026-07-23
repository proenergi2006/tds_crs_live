<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContactType extends Model
{
    protected $table = 'customer_contact_types';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
