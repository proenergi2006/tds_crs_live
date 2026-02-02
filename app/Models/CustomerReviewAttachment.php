<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReviewAttachment extends Model
{
    protected $table = 'customer_review_attchment';
    public $timestamps = false;
    public $incrementing = false; // pk komposit

    protected $fillable = [
        'id_review','id_verification','no_urut',
        'review_attach','review_attach_ori'
    ];

    // optional:
    public function review()
    {
        return $this->belongsTo(CustomerReview::class, 'id_review', 'id_review');
    }
}
