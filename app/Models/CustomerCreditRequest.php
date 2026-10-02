<?php

namespace App\Models;

use App\Enums\CreditProductCategory;
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
        'requested_qty',
        'product_category',
        'financial_review',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_limit' => 'integer',
        'requested_top' => 'integer',
        'requested_qty' => 'decimal:2',
        'product_category' => CreditProductCategory::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function hasFinancialReview(): bool
    {
        return trim(strip_tags((string) $this->financial_review)) !== '';
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
