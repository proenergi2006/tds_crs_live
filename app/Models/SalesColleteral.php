<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesColleteral extends Model
{
    protected $table = 'sales_colleteral';
    public $timestamps = false;
    protected $fillable = ['sales_id','date','amount','item'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'sales_id' => 'integer',
    ];

    public function salesConfirmation(): BelongsTo
    {
        return $this->belongsTo(SalesConfirmation::class, 'sales_id', 'id');
    }
}
