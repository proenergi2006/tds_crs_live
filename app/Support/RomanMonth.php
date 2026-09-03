<?php

namespace App\Support;

class RomanMonth
{
    public static function of(int|string $month): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int) $month] ?? '';
    }
}
