<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttachmentHargaDasar extends Model
{
    protected $table = 'attachment_harga_dasar';
    protected $primaryKey = 'id_attachment_harga_dasar';
    public $timestamps = false;

    protected $fillable = [
        'periode_awal',
        'periode_akhir',
        'catatan',
        'lampiran',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',
    ];
}
