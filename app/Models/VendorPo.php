<?php

namespace App\Models;

use App\Enums\VendorPoApprovalState;
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
        'cfo_result',
        'cfo_summary',
        'cfo_tgl',
        'ceo_result',
        'ceo_summary',
        'ceo_tgl',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];

    protected $appends = ['status_po'];

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

    // Helpers
    public function getStatusPoAttribute(): array
    {
        $state = VendorPoApprovalState::resolve($this);
        return [
            'key'   => $state->name,
            'label' => $state->label(),
        ];
    }

    public function getTotalVolumePo(): float
    {
        return (float) $this->produks->sum('volume_po');
    }

    public function getTotalVolumeTerima(): float
    {
        return (float) $this->receives
            ->flatMap(fn($r) => $r->details)
            ->sum('volume_terima');
    }

    public function getPersenRealisasi(): int
    {
        $total = $this->getTotalVolumePo();
        if ($total <= 0) return 0;
        return (int) min(100, round(($this->getTotalVolumeTerima() / $total) * 100));
    }

    public function getStatusRealisasi(): string
    {
        $terima = $this->getTotalVolumeTerima();
        $po     = $this->getTotalVolumePo();

        if ($terima >= $po && $po > 0) return 'selesai';
        if ($terima > 0)               return 'parsial';
        return 'belum';
    }
}
