<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAdminArnya extends Model
{
    protected $table = 'customer_admin_arnya';
    protected $primaryKey = 'id_arnya';
    public $timestamps = true;

    protected $fillable = [
        'id_customer',
        'outstanding_current',
        'overdue_1_30',
        'overdue_31_60',
        'overdue_61_90',
        'overdue_90_plus',
        'updated_by',
    ];

    protected $casts = [
        'id_arnya' => 'integer',
        'id_customer' => 'integer',
        'outstanding_current' => 'decimal:2',
        'overdue_1_30' => 'decimal:2',
        'overdue_31_60' => 'decimal:2',
        'overdue_61_90' => 'decimal:2',
        'overdue_90_plus' => 'decimal:2',
        'updated_by' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function getTotalArAttribute(): float
    {
        return (float) ($this->outstanding_current
            + $this->overdue_1_30
            + $this->overdue_31_60
            + $this->overdue_61_90
            + $this->overdue_90_plus);
    }
}
