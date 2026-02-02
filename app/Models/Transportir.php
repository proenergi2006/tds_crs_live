<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transportir extends Model
{
    protected $fillable = [
        'nama_perusahaan', 'singkatan', 'kepemilikan', 'terms',
        'id_cabang', 'alamat', 'telpon', 'fax', 'angkutan_kirim',
        'is_active', 'email', 'nomor_hp', 'perizinan', 'masa_berlaku',
        'lampiran', 'catatan', 'fleet', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fleet' => 'boolean',
        'masa_berlaku' => 'date',
    ];

    protected $appends = ['lampiran_url'];

    public function getLampiranUrlAttribute()
    {
        return $this->lampiran ? asset('storage/' . $this->lampiran) : null;
    }

    public function cabang() { return $this->belongsTo(Cabang::class, 'id_cabang')->withDefault(); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withDefault(); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by')->withDefault(); }
}