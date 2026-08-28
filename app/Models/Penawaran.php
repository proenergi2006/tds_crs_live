<?php

namespace App\Models;

use App\Enums\PenawaranBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penawaran extends Model
{
    protected $table      = 'penawarans';
    protected $primaryKey = 'id_penawaran';
    public $timestamps    = false;

    protected $fillable = [
        'id_customer',
        'customer_contact_id',
        'id_cabang',
        'nomor_penawaran',
        'qr_code',
        'masa_berlaku',
        'sampai_dengan',
        'subtotal',
        'ppn11',
        'total',
        'fax',
        'type_pengiriman',
        'dp_persen',
        'dp_keterangan',
        'repayment_persen',
        'repayment_hari',
        'tipe_pembayaran',
        'order_method',
        'toleransi_penyusutan',
        'lokasi_pengiriman',
        'metode',
        'refund',
        'other_cost',
        'perhitungan',
        'keterangan',
        'catatan',
        'syarat_ketentuan',
        'lampiran_tambahan',
        'discount',
        'harga_tebus_setelah_diskon',
        'total_with_oat',
        'harga_dasar',
        'ppn_harga_dasar',
        'grand_total_harga_dasar',
        'oat',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'jenis_penawaran',
        'status',
        'disposisi_penawaran',
        'token_verifikasi',
        'abrasi',
        'user_id',
        'brand',
        'acuan_pembayaran',
    ];

    protected $casts = [
        'brand' => PenawaranBrand::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function customerContact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id', 'id_contact');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function items()
    {
        return $this->hasMany(PenawaranItem::class, 'id_penawaran', 'id_penawaran');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function ongkos()
    {
        return $this->hasMany(PenawaranOngkos::class, 'penawaran_id', 'id_penawaran');
    }

    public function documentApprovals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_penawaran');
    }

    public function latestDocumentApproval(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    public function actedAtForStep(int $stepOrder): ?\Illuminate\Support\Carbon
    {
        return $this->latestDocumentApproval?->steps->firstWhere('step_order', $stepOrder)?->acted_at;
    }
}
