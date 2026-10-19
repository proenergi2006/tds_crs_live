<?php

namespace App\Support;

final class Tax
{
    public const PPN_RATE = 0.11;

    public static function percentLabel(float $rate): string
    {
        $percent = round($rate * 100, 2);

        return rtrim(rtrim(number_format($percent, 2, ',', ''), '0'), ',') . '%';
    }
}
