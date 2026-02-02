<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAllocation extends Model
{
    protected $table = 'stock_allocations';

    protected $fillable = [
        'stock_id', 'id_prd', 'qty', 'allocated_volume', 'harga_tebus', 'created_by'
    ];

    public $timestamps = true;

    // Relasi opsional
    public function stock()
    {
        return $this->belongsTo(\App\Models\Stock::class, 'stock_id', 'id');
    }
}
