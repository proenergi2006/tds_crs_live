<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    protected $table = 'customer_review';
    protected $primaryKey = 'id_review';
    public $timestamps = false;

    protected $fillable = [
        'id_customer', 'reviewed_at', 'review_answers', 'review_attachments',
    ];

    protected $casts = [
        'review_answers' => 'array',
        'review_attachments' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}
