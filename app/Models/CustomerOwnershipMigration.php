<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOwnershipMigration extends Model
{
    protected $table = 'customer_ownership_migrations';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'from_user_id',
        'to_user_id',
        'migrated_by',
        'migrated_at',
        'notes',
    ];

    protected $casts = [
        'migrated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id', 'id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id', 'id');
    }

    public function migratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'migrated_by', 'id');
    }
}
