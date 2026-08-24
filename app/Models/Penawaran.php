<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penawaran extends Model
{
    protected $table      = 'penawarans';
    protected $primaryKey = 'id_penawaran';
    public $timestamps    = false; // gunakan manual timestamp

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
        'bm_result',
        'bm_tanggal',
        'om_result',
        'om_tanggal',
        'approved_at',
        'approved_by',
        'token_verifikasi',
        'catatan_verifikasi',
        'catatan_om',
        'abrasi',
        'user_id',
    ];

    /* Section: relasi */

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    // Kontak tujuan surat penawaran -- nama/jabatan/telepon dibaca live dari kontak customer, tidak di-snapshot.
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

    // morphMany, bukan morphOne -- riwayat siklus approval sebelumnya tetap tersimpan walau ada re-submit setelah reject
    public function documentApprovals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_penawaran');
    }

    public function latestDocumentApproval(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }
}
