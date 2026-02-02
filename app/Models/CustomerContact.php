<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'id_customer';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'pic_decision_name','pic_decision_position','pic_decision_telp','pic_decision_mobile','pic_decision_email',
        'pic_ordering_name','pic_ordering_position','pic_ordering_telp','pic_ordering_mobile','pic_ordering_email',
        'pic_billing_name','pic_billing_position','pic_billing_telp','pic_billing_mobile','pic_billing_email',
        'pic_invoice_name','pic_invoice_position','pic_invoice_telp','pic_invoice_mobile','pic_invoice_email',
        'product_delivery_address',
        'invoice_delivery_addr_primary','invoice_delivery_addr_secondary',
        'pic_fuelman_name','pic_fuelman_position','pic_fuelman_telp','pic_fuelman_mobile','pic_fuelman_email',
    ];

    protected $casts = [
        'id_customer' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        // asumsi model Customer kamu pakai table 'pro_customer' dan PK 'id_customer'
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
