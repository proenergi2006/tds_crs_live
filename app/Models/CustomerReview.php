<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    protected $table = 'customer_review';
    protected $primaryKey = 'id_review';
    public $timestamps = false;

    protected $fillable = [
        'id_verification',
        'review_result', 'review_pic', 'review_tanggal', 'review_summary',
        'review_answers', 'review_attachments',
    ];

    protected $casts = [
        'review_answers' => 'array',
        'review_attachments' => 'array',
    ];

    public function verification()
    {
        return $this->belongsTo(CustomerVerification::class, 'id_verification', 'id_verification');
    }
}
