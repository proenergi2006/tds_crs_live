<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLcr extends Model
{
    protected $table = 'customer_lcr';
    protected $primaryKey = 'id_lcr';
    public $timestamps = false;

    /**
     * Kolom yang boleh di-mass assign.
     */
    protected $fillable = [
        'id_customer','id_wilayah','id_wil_oa',

        // General
        'alamat_survey','prov_survey','kab_survey','telp_survey','fax_survey','tgl_survey',
        'nama_surveyor','review','jenis_usaha','website','hasilsurv','produkvol','picustomer',
        'alat_ukur','toleransi','kompetitor','jam_operasional',

        // Logistik verification + SM
        'logistik_summary','logistik_result','logistik_tanggal','logistik_pic',
        'sm_summary','sm_result','sm_tanggal','sm_pic',

        // Flags
        'flag_disposisi','flag_approval','tgl_approval',

        // Unloading - Tangki
        'tangki','pendukung','quantity_tangki','quality_tangki','catatan_tangki',

        // Unloading - Kapal/Jetty
        'kapal','jetty','quantity_kapal','quality_kapal','catatan_kapal',

        // Location info & proses
        'penjelasan_bongkar',
        'latitude_lokasi','longitude_lokasi','link_google_maps',
        'jarak_depot','max_truk','lsm_portal','min_vol_kirim',
        'rute_lokasi','note_lokasi',

        // Kolom media/gambar per bagian (disimpan sebagai JSON array of {path,url,caption})
        'layout_lokasi','layout_bongkar','kondisi_jalan',
        'kantor_perusahaan','fasilitas_storage','inlet_pipa',
        'alat_ukur_gambar','media_datar','keterangan_lain',

        // Lain-lain
        'jenis_usaha_lain',

        // Audit
        'created_time','created_ip','created_by',
        'lastupdate_time','lastupdate_ip','lastupdate_by',
    ];

    /**
     * Cast tipe data (supaya otomatis decode JSON & format tanggal).
     */
    protected $casts = [
        // Tanggal
        'tgl_survey'       => 'date',
        'logistik_tanggal' => 'datetime',
        'sm_tanggal'       => 'datetime',
        'tgl_approval'     => 'datetime',
        'created_time'     => 'datetime',
        'lastupdate_time'  => 'datetime',

        // Angka
        'id_customer'     => 'integer',
        'id_wilayah'      => 'integer',
        'id_wil_oa'       => 'integer',
        'logistik_result' => 'integer',
        'sm_result'       => 'integer',
        'flag_disposisi'  => 'integer',
        'flag_approval'   => 'integer',
        'latitude_lokasi' => 'float',
        'longitude_lokasi'=> 'float',

        // Array/JSON (general)
        'nama_surveyor'   => 'array',
        'hasilsurv'       => 'array',
        'produkvol'       => 'array',
        'picustomer'      => 'array',
        'kompetitor'      => 'array',
        'jam_operasional' => 'array',

        // Array/JSON (unloading)
        'tangki'          => 'array',
        'pendukung'       => 'array',
        'quantity_tangki' => 'array',
        'quality_tangki'  => 'array',
        'kapal'           => 'array',
        'jetty'           => 'array',
        'quantity_kapal'  => 'array',
        'quality_kapal'   => 'array',

        // Array/JSON (media/gambar per bagian)
        'layout_lokasi'      => 'array',
        'layout_bongkar'     => 'array',
        'kondisi_jalan'      => 'array',
        'kantor_perusahaan'  => 'array',
        'fasilitas_storage'  => 'array',
        'inlet_pipa'         => 'array',
        'alat_ukur_gambar'   => 'array',
        'media_datar'        => 'array',
        'keterangan_lain'    => 'array',
    ];

    /**
     * Relasi ke Customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    /**
     * Relasi ke Wilayah Angkut (OA).
     */
    public function wilayahAngkut(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WilayahAngkut::class, 'id_wil_oa', 'id');
    }

    /**
     * Accessor opsional: coordinates (lat, lng) → JSON.
     * Aktifkan $appends jika ingin ikut di-serialize.
     */
    // protected $appends = ['coordinates'];
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude_lokasi === null || $this->longitude_lokasi === null) {
            return null;
        }
        return [
            'lat' => (float) $this->latitude_lokasi,
            'lng' => (float) $this->longitude_lokasi,
        ];
    }
}
