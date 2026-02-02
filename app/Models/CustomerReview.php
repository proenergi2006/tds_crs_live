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
        'review_result','review_pic','review_tanggal','review_summary',
        'review_attach','review_attach_ori',
        'jenis_asset','kelengkapan_dok_tagihan','alur_proses_periksaan',
        'jadwal_penerimaan','background_bisnis','lokasi_depo','opportunity_bisnis',
        // kalau kamu pakai review1..review16 juga mau isi dari UI:
        'review1','review2','review3','review4','review5','review6','review7','review8',
        'review9','review10','review11','review12','review13','review14','review15','review16',
    ];

    public function verification()
    {
        return $this->belongsTo(CustomerVerification::class, 'id_verification', 'id_verification');
    }

    public function attachments()
    {
        // relasi manual (pk komposit)
        return $this->hasMany(CustomerReviewAttachment::class, 'id_review', 'id_review');
    }
}
