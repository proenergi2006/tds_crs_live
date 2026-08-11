<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranProenergi extends Model
{
    protected $table      = 'penawarans_proenergi';
    protected $primaryKey = 'id_penawaran';
    public $timestamps    = false; // gunakan manual timestamp

    protected $fillable = [
        'id_customer',
        'id_cabang',
        'nomor_penawaran',
        'qr_code',
        'masa_berlaku',
        'sampai_dengan',
        'subtotal',
        'ppn11',
        'total',
        'kepada',
        'jabatan',
        'telepon',
        'nama',
        'alamat',
        'fax',
        'type_pengiriman',
        'dp_persen',
    'dp_keterangan',
    'repayment_persen',
    'repayment_hari',
        'tipe_pembayaran',
        'acuan_pembayaran',
        'top_hari',
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
        'catatan_verifikasi',
        'catatan_om',
        'token_verifikasi',
        'abrasi',
        'user_id',
    ];

    /******** Relasi ********/

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function produk_harga()
{
    return $this->belongsTo(\App\Models\ProdukHarga::class, 'id_produk', 'id_produk');
}

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function items()
    {
        return $this->hasMany(PenawaranItemProenergi::class, 'id_penawaran', 'id_penawaran');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function ongkos()
    {
        return $this->hasMany(PenawaranOngkosProenergi::class, 'penawaran_id', 'id_penawaran');
    }

    /**
     * Riwayat approval polymorphic (`document_approvals`, code=penawaran_proenergi),
     * mengikuti pola yang sama dengan CustomerLcr::documentApprovals().
     * Pakai morphMany supaya riwayat siklus sebelumnya tetap tersimpan walau
     * ada re-submit setelah reject.
     */
    public function documentApprovals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_penawaran');
    }

    public function latestDocumentApproval(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }
}
