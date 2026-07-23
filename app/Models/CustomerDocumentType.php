<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDocumentType extends Model
{
    protected $table = 'customer_document_types';
    protected $primaryKey = 'id_document_type';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'requires_number',
        'category',
        'sort_order',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'requires_number' => 'boolean',
    ];
}
