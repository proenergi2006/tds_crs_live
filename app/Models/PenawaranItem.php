<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenawaranItem extends Model
{
    protected $table      = 'penawaran_items';
    protected $primaryKey = 'id_penawaran_item';
    public $timestamps    = true;

    protected $fillable = [
        'id_penawaran',
        'id_produk',
        'source_branch_id',
        'product_price_id',
        'persen',
        'volume_order',
        'harga_tebus',
        'jumlah_harga',
    ];

    protected $casts = [
        'volume_order' => 'integer',
        'harga_tebus'  => 'integer',
        'jumlah_harga' => 'integer',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran', 'id_penawaran');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function sourceCabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'source_branch_id', 'id_cabang');
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id', 'id');
    }
}
