<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPo extends Model
{
    protected $table = 'vendor_pos';
    protected $primaryKey = 'id_po';
    public $timestamps = false;

    protected $fillable = [
        'id_vendor',
        'id_terminal',
        'nomor_po',
        'tanggal_inven',
        'kd_tax',
        'terms',
        'terms_day',
        'subtotal',
        'ppn11',
        'total_order',
        'keterangan',
        'terms_condition',
        'disposisi_po',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'id_vendor', 'id_vendor');
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal::class, 'id_terminal', 'id_terminal');
    }

    public function produks()
    {
        // relasi ke detail PO (VendorPoProduk)
        return $this->hasMany(VendorPoProduk::class, 'id_po', 'id_po');
    }

    public function receives()
    {
        // relasi ke header receive (ReceiveItem)
        return $this->hasMany(ReceiveItem::class, 'po_id', 'id_po');
    }
}
