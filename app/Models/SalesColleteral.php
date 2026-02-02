<?php

// app/Models/SalesColleteral.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesColleteral extends Model
{
    protected $table = 'sales_colleteral'; // sesuaikan
    public $timestamps = false;
    protected $fillable = ['sales_id','date','amount','item'];
}
