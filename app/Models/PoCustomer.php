<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoCustomer extends Model
{
    protected $table = 'po_customers';
    protected $primaryKey = 'id_poc';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'id_penawaran',
        'top_poc',
        'nomor_poc',
        'tanggal_poc',
        'supply_date',
        'harga_poc',
        'volume_poc',
        'produk_poc',
        'lampiran_poc',
        'lampiran_poc_ori',
        'created_time',
        'created_ip',
        'created_by',
        'lastupdate_time',
        'lastupdate_ip',
        'lastupdate_by',
        'po_notif',
        'st_bayar_po',
        'tgl_bayar_po',
        'keterangan_bayar',
        'is_edit',
    ];

    protected $casts = [
        'tanggal_poc' => 'date',
        'supply_date' => 'date',
        'harga_poc' => 'decimal:4',
        'volume_poc' => 'integer',
        'created_time' => 'datetime',
        'lastupdate_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran', 'id_penawaran');
    }

    public function salesConfirmation(): HasOne
    {
        return $this->hasOne(SalesConfirmation::class, 'po_customer_id', 'id_poc');
    }

    public function poCustomerPlans(): HasMany
    {
        return $this->hasMany(PoCustomerPlan::class, 'id_poc', 'id_poc');
    }

    public function getStatusKeyAttribute(): string
    {
        return match (true) {
            $this->salesConfirmation === null => 'awaiting_sc',
            (int) $this->salesConfirmation->getRawOriginal('disposisi') === 4 => 'done',
            default => 'sc_in_progress',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'awaiting_sc' => 'Menunggu Sales Confirmation',
            'sc_in_progress' => 'Sales Confirmation Diproses',
            'done' => 'Selesai',
        };
    }
}
