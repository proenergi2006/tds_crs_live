<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiveItem extends Model
{
    use SoftDeletes;

    protected $table = 'receive_items';

    protected $fillable = [
        'po_id',
        'received_at',
        'nama_pic',
        'file_path',
    ];

    protected $appends = ['no_gr'];

    public function vendorPo(): BelongsTo
    {
        return $this->belongsTo(VendorPo::class, 'po_id', 'id_po');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReceiveItemProduk::class, 'receive_item_id', 'id');
    }

    public function getNoGrAttribute(): string
    {
        $year = $this->received_at
            ? Carbon::parse($this->received_at)->year
            : now()->year;
        return 'GR-' . $year . '-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }
}
