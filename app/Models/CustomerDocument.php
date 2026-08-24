<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    protected $table = 'customer_documents';
    protected $primaryKey = 'id_document';

    protected $fillable = [
        'id_customer',
        'id_document_type',
        'document_name',
        'document_number',
        'file_path',
        'file_name',
        'uploaded_at',
        'uploaded_by',
        'id_lcr',
        'notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    // Nullable -- cuma keisi buat dokumen foto LCR (id_document_type=lcr_*), lainnya NULL.
    public function lcr(): BelongsTo
    {
        return $this->belongsTo(CustomerLcr::class, 'id_lcr', 'id_lcr');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(CustomerDocumentType::class, 'id_document_type');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
