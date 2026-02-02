<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $primaryKey = 'id_customer';
    public $timestamps = false;

    protected $fillable = [
        // dasar
        'id_user',
        'email',
        'id_provinsi',
        'id_kabupaten',
        'postal_code',
        'telepon',
        'jenis_customer',
        'nama_perusahaan',
        'alamat_perusahaan',
        'fax',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',

        // tambahan yang diminta
        'kode_pelanggan',
        'website_customer',
        'tipe_bisnis',
        'tipe_bisnis_lain',
        'ownership',
        'ownership_lain',
        'nomor_sertifikat',
        'nomor_sertifikat_file',
        'nomor_npwp',
        'nomor_npwp_file',
        'nomor_siup',
        'nomor_siup_file',
        'nomor_tdp',
        'nomor_tdp_file',
        'dokumen_lainnya',
        'dokumen_lainnya_file',
        'need_update',
        'is_generated_link',
        'count_update',
        'is_verified',
        'status_customer',
        'prospect_customer_date',
        'prospect_evaluated',
        'fix_customer_since',
        'fix_customer_redate',
        'jenis_payment',
        'top_payment',
        'jenis_net',
        'credit_limit',
        'credit_limit_diajukan',
        'id_verification',
        'ajukan',
        'induk_perusahaan',
        'kecamatan_customer',
        'kelurahan_customer',
        'print_product',
        'id_cabang',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'id_provinsi', 'id_provinsi');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten', 'id_kabupaten');
    }

    public function cabang()
{
    return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
}

public function lcr(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(\App\Models\CustomerLcr::class, 'id_customer', 'id_customer');
}

public function penawarans(): HasMany
{
    return $this->hasMany(Penawaran::class, 'id_customer', 'id_customer');
}

public function verifications()
{
    return $this->hasMany(\App\Models\CustomerVerification::class, 'id_customer', 'id_customer');
}

}
