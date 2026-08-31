<?php

namespace App\Enums;

enum ProductPriceCogsBasis: string
{
    case Loco   = 'loco';
    case Franco = 'franco';

    public function label(): string
    {
        return match ($this) {
            self::Loco   => 'Loco (harga di lokasi penjual, belum termasuk ongkos kirim)',
            self::Franco => 'Franco (harga sudah termasuk ongkos kirim sampai tujuan)',
        };
    }
}
