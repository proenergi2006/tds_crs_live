<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditRequest extends Model
{
    protected $table = 'customer_credit_requests';
    protected $primaryKey = 'id_request';

    protected $fillable = [
        'id_customer',
        'requested_limit',
        'requested_top',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_limit' => 'integer',
        'requested_top'   => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
