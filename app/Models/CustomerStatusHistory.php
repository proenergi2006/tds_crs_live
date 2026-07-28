<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\CustomerStatusTriggerSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStatusHistory extends Model
{
    protected $table = 'customer_status_history';
    protected $primaryKey = 'id_history';

    protected $fillable = [
        'id_customer',
        'status',
        'changed_at',
        'changed_by',
        'trigger_source',
        'notes',
    ];

    protected $casts = [
        'status'         => CustomerStatus::class,
        'changed_at'     => 'datetime',
        'trigger_source' => CustomerStatusTriggerSource::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'id');
    }
}
