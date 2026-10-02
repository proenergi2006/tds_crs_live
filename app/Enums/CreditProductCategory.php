<?php

namespace App\Enums;

enum CreditProductCategory: string
{
    case CrushedStone = 'crushed_stone';
    case Polimer = 'polimer';

    public function label(): string
    {
        return match ($this) {
            self::CrushedStone => 'Crushed Stone',
            self::Polimer => 'Polimer',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::CrushedStone => 'm³',
            self::Polimer => 'totes',
        };
    }
}
