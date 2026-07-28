<?php

namespace App\Models;

use App\Enums\CustomerDocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    protected $table = 'customer_documents';
    protected $primaryKey = 'id_document';

    protected $fillable = [
        'id_customer',
        'id_document_type',
        'document_number',
        'file_path',
        'file_name',
        'uploaded_at',
        'uploaded_by',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'status'      => CustomerDocumentStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
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
