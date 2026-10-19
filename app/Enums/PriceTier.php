<?php

namespace App\Enums;

use App\Models\ProductPrice;

enum PriceTier: string
{
    case Tier1 = 'tier_1';
    case Tier2 = 'tier_2';
    case Tier3 = 'tier_3';

    public function label(): string
    {
        return match ($this) {
            self::Tier1 => 'Tier I',
            self::Tier2 => 'Tier II',
            self::Tier3 => 'Tier III',
        };
    }

    public static function forUnitPrice(float $unitPrice, ProductPrice $limits): self
    {
        return match (true) {
            $unitPrice >= (float) $limits->bm_price => self::Tier1,
            $unitPrice >= (float) $limits->om_price => self::Tier2,
            default                                 => self::Tier3,
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Tier1 => 1,
            self::Tier2 => 2,
            self::Tier3 => 3,
        };
    }

    public static function highest(iterable $tiers): self
    {
        $highest = self::Tier1;

        foreach ($tiers as $tier) {
            if ($tier->rank() > $highest->rank()) {
                $highest = $tier;
            }
        }

        return $highest;
    }
}
