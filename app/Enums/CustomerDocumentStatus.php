<?php

namespace App\Enums;

enum CustomerDocumentStatus: string
{
    case Ada      = 'ada';
    case TidakAda = 'tidak_ada';
    case Diminta  = 'diminta';

    public function label(): string
    {
        return match ($this) {
            self::Ada      => 'Ada',
            self::TidakAda => 'Tidak Ada',
            self::Diminta  => 'Diminta',
        };
    }
}
