<?php

namespace App\Models;

use App\Enums\ProductPriceCogsBasis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'price_period_id',
        'branch_id',
        'product_id',
        'price_list',
        'price_list_pe',
        'bm_price',
        'cogs_price',
        'cogs_basis',
        'cogs_material_price',
        'cogs_transport_price',
        'margin_material_amount',
        'margin_transport_amount',
        'om_price',
        'ceo_price',
        'notes',
        'needs_reverification',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price_list'            => 'decimal:2',
        'price_list_pe'         => 'decimal:2',
        'bm_price'              => 'decimal:2',
        'cogs_price'            => 'decimal:2',
        'cogs_material_price'   => 'decimal:2',
        'cogs_transport_price'  => 'decimal:2',
        'margin_material_amount'  => 'decimal:2',
        'margin_transport_amount' => 'decimal:2',
        'om_price'              => 'decimal:2',
        'ceo_price'             => 'decimal:2',
        'needs_reverification'  => 'boolean',
    ];

    public const VERIFIER_COLUMNS = ['price_list', 'price_list_pe', 'margin_material_amount', 'bm_price', 'om_price', 'ceo_price'];

    public const COGS_COLUMNS = ['cogs_price', 'cogs_basis', 'cogs_material_price', 'cogs_transport_price'];

    public const TIER_ORDER = ['price_list', 'bm_price', 'om_price', 'ceo_price'];

    private const TIER_LABELS = [
        'price_list'    => 'Price List TDS',
        'bm_price'      => 'Approval BM',
        'om_price'      => 'Approval OM',
        'ceo_price'     => 'Approval CEO',
    ];

    private static function tierPairs(): array
    {
        $pairs = [];

        $pairs[] = ['price_list', 'bm_price', false];
        $pairs[] = ['price_list', 'om_price', true];
        $pairs[] = ['price_list', 'ceo_price', true];
        $pairs[] = ['bm_price', 'om_price', true];
        $pairs[] = ['bm_price', 'ceo_price', true];
        $pairs[] = ['om_price', 'ceo_price', true];

        return $pairs;
    }

    public static function tierOrderErrors(array $values): array
    {
        $errors = [];
        $singlePrice = self::isSinglePriceTier($values);

        foreach (self::tierPairs() as [$higher, $lower, $strict]) {
            $high = (float) ($values[$higher] ?? 0);
            $low = (float) ($values[$lower] ?? 0);

            if ($high <= 0 || $low <= 0 || isset($errors[$lower]) || ($strict && $singlePrice)) {
                continue;
            }

            if ($strict ? $high <= $low : $high < $low) {
                $errors[$lower] = $strict
                    ? self::TIER_LABELS[$lower] . ' harus lebih kecil dari ' . self::TIER_LABELS[$higher] . '.'
                    : self::TIER_LABELS[$lower] . ' tidak boleh lebih besar dari ' . self::TIER_LABELS[$higher] . '.';
            }
        }

        return $errors;
    }

    private static function isSinglePriceTier(array $values): bool
    {
        $bm = (float) ($values['bm_price'] ?? 0);

        return $bm > 0
            && $bm === (float) ($values['om_price'] ?? 0)
            && $bm === (float) ($values['ceo_price'] ?? 0);
    }

    public function hasValidTierOrder(): bool
    {
        return self::tierOrderErrors($this->only(self::TIER_ORDER)) === [];
    }

    public static function tierViolationSql(string $table = 'product_prices'): string
    {
        $singlePrice = sprintf(
            '(%1$s.bm_price > 0 AND %1$s.bm_price = %1$s.om_price AND %1$s.om_price = %1$s.ceo_price)',
            $table
        );

        $conditions = array_map(
            fn (array $pair) => sprintf(
                '(%1$s.%2$s > 0 AND %1$s.%3$s > 0 AND %1$s.%2$s %4$s %1$s.%3$s%5$s)',
                $table,
                $pair[0],
                $pair[1],
                $pair[2] ? '<=' : '<',
                $pair[2] ? " AND NOT {$singlePrice}" : ''
            ),
            self::tierPairs()
        );

        return '(' . implode(' OR ', $conditions) . ')';
    }

    public function scopeWithCompleteTierOrder($query)
    {
        foreach (self::TIER_ORDER as $column) {
            $query->where("product_prices.{$column}", '>', 0);
        }

        return $query->whereRaw('NOT ' . self::tierViolationSql());
    }

    public function scopeWithInvalidTierOrder($query)
    {
        return $query->whereRaw(self::tierViolationSql());
    }

    public static function submitsVerifierColumns(array $row): bool
    {
        foreach (self::VERIFIER_COLUMNS as $column) {
            if (array_key_exists($column, $row) && $row[$column] !== null) {
                return true;
            }
        }

        return false;
    }

    public function syncReverificationFlag(bool $verifierSubmitted): void
    {
        if ($verifierSubmitted) {
            $this->needs_reverification = false;

            return;
        }

        if ($this->isDirty(self::COGS_COLUMNS)) {
            $this->needs_reverification = true;
        }
    }

    public function francoTransportPrice(): ?float
    {
        if ($this->cogs_basis !== ProductPriceCogsBasis::Franco->value
            || $this->cogs_transport_price === null
            || $this->margin_transport_amount === null) {
            return null;
        }

        return round((float) $this->cogs_transport_price + (float) $this->margin_transport_amount, 2);
    }

    public function materialCogsPrice(): ?float
    {
        if ($this->cogs_material_price !== null) {
            return (float) $this->cogs_material_price;
        }

        if ($this->cogs_basis === ProductPriceCogsBasis::Franco->value) {
            return null;
        }

        return $this->cogs_price !== null ? (float) $this->cogs_price : null;
    }

    public function priceListForBrand(string $brand): float
    {
        return $brand === 'proenergi' && (float) $this->price_list_pe > 0
            ? (float) $this->price_list_pe
            : (float) $this->price_list;
    }

    public function pricePeriod(): BelongsTo
    {
        return $this->belongsTo(PricePeriod::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id', 'id_cabang');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'product_id', 'id_produk');
    }
}
