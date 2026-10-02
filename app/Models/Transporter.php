<?php

namespace App\Models;

use App\Enums\TransportCapability;
use App\Enums\TransporterOwnership;
use App\Models\Concerns\HasLogisticDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporter extends Model
{
    use HasLogisticDocuments;

    protected $fillable = [
        'company_name',
        'short_name',
        'ownership',
        'terms',
        'id_cabang',
        'address',
        'phone',
        'fax',
        'transport_capability',
        'is_active',
        'email',
        'mobile_phone',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ownership' => TransporterOwnership::class,
        'transport_capability' => TransportCapability::class,
    ];

    public function documentNamingLabel(): string
    {
        return $this->short_name ?: $this->company_name;
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang')->withDefault();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withDefault();
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    public function transportTariffs(): HasMany
    {
        return $this->hasMany(TransportTariff::class);
    }
}
