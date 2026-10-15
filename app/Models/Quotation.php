<?php

namespace App\Models;

use App\Enums\PriceTier;
use App\Enums\ProductPriceCogsBasis;
use App\Enums\QuotationBrand;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationDisposisi;
use App\Enums\QuotationStatus;
use App\Enums\QuotationTransportMode;
use App\Enums\QuotationTransportSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class Quotation extends Model
{
    public const PPN_RATE = 0.11;

    private const JENIS_PRODUK_POLIMER = 11;

    protected $table   = 'quotations';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'customer_contact_id',
        'branch_id',
        'price_period_id',
        'nomor_penawaran',
        'qr_code',
        'masa_berlaku',
        'sampai_dengan',
        'subtotal',
        'ppn11',
        'total',
        'fax',
        'type_pengiriman',
        'dp_persen',
        'dp_keterangan',
        'repayment_persen',
        'repayment_hari',
        'tipe_pembayaran',
        'order_method',
        'toleransi_penyusutan',
        'lokasi_pengiriman',
        'metode',
        'refund',
        'other_cost',
        'perhitungan',
        'keterangan',
        'catatan',
        'syarat_ketentuan',
        'lampiran_tambahan',
        'discount',
        'harga_tebus_setelah_diskon',
        'total_with_oat',
        'oat',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'jenis_penawaran',
        'status',
        'disposisi_penawaran',
        'token_verifikasi',
        'abrasi',
        'user_id',
        'brand',
        'acuan_pembayaran',
        'price_tier',
        'transport_basis',
    ];

    protected $casts = [
        'brand'               => QuotationBrand::class,
        'disposisi_penawaran' => QuotationDisposisi::class,
        'price_tier'          => PriceTier::class,
        'status'              => QuotationStatus::class,
        'transport_basis'     => ProductPriceCogsBasis::class,
    ];

    protected $appends = [
        'disposisi_label',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id_customer');
    }

    public function customerContact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id', 'id_contact');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id', 'id_cabang');
    }

    public function pricePeriod(): BelongsTo
    {
        return $this->belongsTo(PricePeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transports(): HasMany
    {
        return $this->hasMany(QuotationTransport::class);
    }

    public function poCustomers(): HasMany
    {
        return $this->hasMany(PoCustomer::class, 'quotation_id');
    }

    public function transportMode(): ?QuotationTransportMode
    {
        $snapshotted = $this->items->first(fn ($item) => $item->transport_snapshot !== null);

        return QuotationTransportSource::tryFrom((string) ($snapshotted?->transport_snapshot['source'] ?? ''))?->mode();
    }

    public function isNonCommercialEditableStatus(): bool
    {
        return in_array($this->status, [QuotationStatus::WaitingVerification, QuotationStatus::Approved], true);
    }

    public function isReopenableStatus(): bool
    {
        return in_array($this->status, [QuotationStatus::Approved, QuotationStatus::WaitingVerification], true);
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === QuotationStatus::WaitingVerification
            && $this->activeVerificationStep() !== null;
    }

    public function isReopenable(): bool
    {
        return $this->isReopenableStatus()
            && $this->isValidOn(now())
            && ! $this->poCustomers()->exists();
    }

    public function isManagedBy(User $user): bool
    {
        return $user->can($this->brand->managePermission()) && (int) $this->user_id === (int) $user->id;
    }

    public function isViewableBy(User $user): bool
    {
        return $user->can($this->brand->viewAnyPermission())
            || ((int) $this->user_id === (int) $user->id && QuotationBrand::viewableBy($user) !== []);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (QuotationBrand::viewableBy($user) === []) {
            return $query->whereRaw('1 = 0');
        }

        $anyBrands = QuotationBrand::viewableAnyBy($user);

        return $query->where(fn (Builder $q) => $q->whereIn('brand', $anyBrands)->orWhere('user_id', $user->id));
    }

    public function isPolimer(): bool
    {
        $this->loadMissing('items.product');

        return $this->items->contains(fn (QuotationItem $item) => (int) $item->product?->id_jenis === self::JENIS_PRODUK_POLIMER);
    }

    public function canEditNonCommercialDetails(User $user): bool
    {
        return $this->isNonCommercialEditableStatus() && $user->can('update', $this);
    }

    public function documentApprovals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable', 'approvable_type', 'approvable_id', 'id');
    }

    public function latestDocumentApproval(): MorphOne
    {
        return $this->documentApprovals()->one()->latestOfMany('id_approval');
    }

    public function scopeWithDppAggregates($query)
    {
        return $query
            ->withSum('items as total_volume', 'quantity')
            ->withSum('items as dpp_total', DB::raw('ROUND(quotation_items.quantity * (quotation_items.unit_price + quotation_items.transport_price), 2)'))
            ->withSum('items as transport_total_sum', DB::raw('quotation_items.quantity * quotation_items.transport_price'));
    }

    public function aggregatedDpp(): float
    {
        return round((float) $this->dpp_total, 2);
    }

    public function aggregatedTransportTotal(): float
    {
        return round((float) $this->transport_total_sum, 2);
    }

    public function subtotalMaterial(): float
    {
        return round((float) $this->items->sum(fn ($item) => (float) $item->line_total), 2);
    }

    public function totalQuantity(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) $item->quantity);
    }

    public function transportTotal(): float
    {
        return round((float) $this->items->sum(fn ($item) => (float) $item->quantity * (float) $item->transport_price), 2);
    }

    public function dpp(): float
    {
        return round((float) $this->items->sum(fn (QuotationItem $item) => $item->finalTotal()), 2);
    }

    public function ppnAmount(): float
    {
        return round($this->dpp() * self::PPN_RATE, 2);
    }

    public function grandTotal(): float
    {
        return $this->dpp() + $this->ppnAmount();
    }

    public function weightedFinalPrice(): float
    {
        $quantity = $this->totalQuantity();

        return $quantity > 0 ? $this->dpp() / $quantity : 0.0;
    }

    public function activeVerificationStep(): ?DocumentApprovalStep
    {
        $cycle = $this->latestDocumentApproval;

        if (! $cycle || $cycle->status !== DocumentApprovalStatus::InProgress) {
            return null;
        }

        return $cycle->steps->firstWhere('step_order', $cycle->current_step_order);
    }

    public function waitingSince(): ?\Illuminate\Support\Carbon
    {
        $cycle = $this->latestDocumentApproval;
        $step  = $this->activeVerificationStep();

        if (! $cycle || ! $step) {
            return null;
        }

        $previous = $cycle->steps
            ->where('step_order', '<', $step->step_order)
            ->sortByDesc('step_order')
            ->first();

        return $previous?->acted_at ?? $cycle->started_at;
    }

    public function finalApprovedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->latestDocumentApproval?->steps
            ->filter(fn ($s) => $s->status === DocumentApprovalStepStatus::Approved)
            ->sortByDesc('step_order')
            ->first()?->acted_at;
    }

    public function scopeWaitingAtRoles(Builder $query, array $roleIds): Builder
    {
        return $query->where('status', QuotationStatus::WaitingVerification)
            ->whereHas('documentApprovals', fn ($cycle) => $cycle
                ->where('status', DocumentApprovalStatus::InProgress)
                ->whereHas('steps', fn ($step) => $step
                    ->whereColumn('document_approval_steps.step_order', 'document_approvals.current_step_order')
                    ->whereIn('id_role', $roleIds)));
    }

    public function getDisposisiLabelAttribute(): ?string
    {
        return $this->disposisi_penawaran?->label();
    }

    public function scopeValidOn(Builder $query, Carbon|string $date): Builder
    {
        $day = Carbon::parse($date)->toDateString();

        return $query->whereDate('masa_berlaku', '<=', $day)
            ->whereDate('sampai_dengan', '>=', $day);
    }

    public function isValidOn(Carbon|string $date): bool
    {
        if (! $this->masa_berlaku || ! $this->sampai_dengan) {
            return false;
        }

        $day = Carbon::parse($date)->toDateString();

        return Carbon::parse($this->masa_berlaku)->toDateString() <= $day
            && Carbon::parse($this->sampai_dengan)->toDateString() >= $day;
    }

    public function validityPeriodLabel(): string
    {
        $from = $this->masa_berlaku ? Carbon::parse($this->masa_berlaku)->format('d/m/Y') : '-';
        $to = $this->sampai_dengan ? Carbon::parse($this->sampai_dengan)->format('d/m/Y') : '-';

        return "{$from} – {$to}";
    }
}
