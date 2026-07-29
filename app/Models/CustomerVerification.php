<?php

namespace App\Models;

use App\Enums\CustomerKycStatus;
use App\Enums\DocumentApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CustomerVerification extends Model
{
    protected $table = 'customer_verifications';
    protected $primaryKey = 'id_verification';
    public $timestamps = false;

    protected $fillable = [
        'id_customer',
        'verification_token',
        'is_submitted',
        'is_forwarded',
        'kyc_status',
        'is_active',
        'expired_at',

        'legal_data',
        'legal_summary',
        'legal_result',
        'legal_processed_at',
        'legal_pic',

        'finance_data',
        'finance_summary',
        'finance_result',
        'finance_processed_at',
        'finance_pic',

        'logistics_data',
        'logistics_summary',
        'logistics_result',
        'logistics_processed_at',
        'logistics_pic',

        'data_type',
        'finance_data_kyc',
    ];

    protected $casts = [
        'is_submitted' => 'boolean',
        'is_forwarded' => 'boolean',
        'kyc_status'   => CustomerKycStatus::class,
        'is_active'    => 'boolean',

        'legal_result'     => 'integer',
        'finance_result'   => 'integer',
        'logistics_result' => 'integer',
        'data_type'        => 'integer',

        'legal_processed_at'     => 'datetime',
        'finance_processed_at'   => 'datetime',
        'logistics_processed_at' => 'datetime',
        'expired_at'              => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'id_customer', 'id_customer');
    }

    /**
     * Riwayat approval polymorphic (sistem approval generik baru). `morphMany`
     * (bukan `morphOne`) karena `document_approvals` tidak mendukung
     * reopen/multiple cycle per row saat ini — tidak ada guard yang mencegah
     * lebih dari satu row approval per verification di masa depan (mis. re-submit
     * setelah reject), jadi morphMany lebih aman sebagai default daripada
     * mengasumsikan strictly-one.
     */
    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(\App\Models\DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id_verification');
    }

    /**
     * (CA7) Siklus approval TERBARU (per id_approval) untuk verification ini,
     * dipakai queue Marketing untuk mengecek "apakah siklus terakhir rejected"
     * tanpa perlu subquery manual -- standar Eloquent one-of-many
     * (`latestOfMany`) di atas relasi morphMany yang sudah ada.
     */
    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    /**
     * (Fase 0 / Task F0-C) Best-effort mapping ke vocabulary stage label LAMA
     * (5 stage: Marketing/Draft, Admin, Logistik, BM, OM — dulu berasal dari
     * kolom `disposisi_result` yang sudah di-drop F0-B) dari sistem approval
     * BARU (2 step formal: Admin Finance -> BM).
     *
     * 'Logistik'/'OM' SENGAJA TIDAK BISA lagi diproduksi oleh method ini --
     * kedua step itu sudah dihapus dari alur approval sejak pivot CA-Amend
     * (2026-07-10), tidak ada padanan di `document_approvals`/
     * `document_approval_steps` untuk mereka. Ini FINDING yang dilaporkan di
     * Task F0-C (customer-kyc-lapis1-migration.md), bukan keputusan final --
     * dipertahankan sebagai satu sumber (dipakai baik oleh
     * CustomerController::formatLatestVerification() maupun
     * CustomerVerificationController::reviewShow()) supaya kedua tempat itu
     * konsisten.
     *
     * Method ini defensif terhadap lazy-load (dipakai juga dari endpoint
     * single-record seperti reviewShow() yang tidak selalu eager-load
     * relasi) -- tapi caller yang memproses banyak row (mis.
     * CustomerController::index()) WAJIB eager-load
     * `latestVerification.latestDocumentApproval.steps` lebih dulu supaya
     * tidak N+1.
     */
    public function stageLabel(): string
    {
        if (!$this->is_forwarded) {
            return 'Marketing/Draft';
        }

        $cycle = $this->relationLoaded('latestDocumentApproval')
            ? $this->latestDocumentApproval
            : $this->latestDocumentApproval()->first();

        if (!$cycle || $cycle->status !== DocumentApprovalStatus::InProgress) {
            // Approved/Rejected/tidak ada siklus sama sekali: model lama
            // tidak punya label 1:1 untuk state ini (step final lama 'OM'
            // sudah tidak ada, dan disposisi_result lama tidak membedakan
            // closed-approved vs closed-rejected di level label) -- fallback
            // ke default lama ('-').
            return '-';
        }

        $steps = $cycle->relationLoaded('steps') ? $cycle->steps : $cycle->steps()->get();
        $firstStepOrder = $steps->first()?->step_order;

        // 2 step formal: Admin Finance SELALU step pertama, BM SELALU step
        // terakhir (lihat catatan CA-Amend di
        // CustomerVerificationController::ROLE_ADMIN_FINANCE/ROLE_BM) --
        // dipetakan ke label lama yang paling dekat maknanya, bukan label
        // baru yang dikarang.
        return $cycle->current_step_order === $firstStepOrder ? 'Admin' : 'BM';
    }
}
