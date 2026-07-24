<?php

namespace App\Models;

use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerPaymentTermBasis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    protected $table = 'customer_payment';
    protected $primaryKey = 'id_customer';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'payment_schedule','payment_schedule_other',
        'payment_method','payment_method_other',
        'payment_term','payment_term_days','payment_term_basis',
        'invoice','extra_notes',
        'calculate_method','bank_name','currency','bank_address','account_number',
        'credit_facility','creditor',
    ];

    protected $casts = [
        'id_customer'        => 'integer',
        'payment_schedule'   => 'integer',
        'payment_method'     => 'integer',
        'payment_term'       => CustomerPaymentTerm::class,
        'payment_term_days'  => 'integer',
        'payment_term_basis' => CustomerPaymentTermBasis::class,
        'invoice'            => 'integer',
        'credit_facility'    => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
