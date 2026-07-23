<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'id_contact';

    protected $fillable = [
        'id_customer',
        'id_contact_type',
        'id_lcr',
        'full_name',
        'position',
        'phone',
        'mobile',
        'email',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function contactType(): BelongsTo
    {
        return $this->belongsTo(CustomerContactType::class, 'id_contact_type');
    }

    public function lcr(): BelongsTo
    {
        return $this->belongsTo(CustomerLcr::class, 'id_lcr', 'id_lcr');
    }
}
