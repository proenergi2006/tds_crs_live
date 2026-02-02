<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $primaryKey = 'id_vendor';
    public $timestamps = false;

    protected $fillable = [
        'nama_vendor',
        'inisial',
        'catatan',
        'is_active',
        'created_time',
        'created_by',
        'lastupdate_time',
        'lastupdate_by',

        // Dokumen
        'npwp_number','npwp_file',
        'nib_number','nib_file',
        'sppkp_number','sppkp_file',
        'bank_account_letter_file',
        'company_profile_file',
    ];
}
