<?php

namespace App\Models;

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
        'email_billing','alamat_billing',
        'prov_billing','kab_billing','postalcode_billing',
        // Kolom baru berbasis kode BPS (laravel-nusa-address-full-migration),
        // di samping prov_billing/kab_billing lama — lama TIDAK dihapus.
        'province_id','regency_id','district_id','village_id',
        'telp_billing','fax_billing',
        'payment_schedule','payment_schedule_other',
        'payment_method','payment_method_other',
        'invoice','ket_extra',
        'kecamatan_billing','kelurahan_billing',
        'calculate_method','bank_name','curency','bank_address','account_number',
        'credit_facility','creditor',
    ];

    protected $casts = [
        'id_customer'      => 'integer',
        'prov_billing'     => 'integer',
        'kab_billing'      => 'integer',
        'payment_schedule' => 'integer',
        'payment_method'   => 'integer',
        'invoice'          => 'integer',
        'credit_facility'  => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    // Relasi opsional—aktifkan kalau kamu punya modelnya
    public function provinsiBilling(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'prov_billing', 'id_provinsis');
    }

    public function kabupatenBilling(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kab_billing', 'id_kabupaten');
    }

    /*
     * Relasi baru berbasis data BPS (laravel-nusa-address-full-migration),
     * di samping provinsiBilling()/kabupatenBilling() lama di atas — lama
     * tidak dihapus.
     */
    public function provinceBilling(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function regencyBilling(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function districtBilling(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function villageBilling(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
    }
}
