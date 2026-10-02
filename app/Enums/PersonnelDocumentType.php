<?php

namespace App\Enums;

enum PersonnelDocumentType: string
{
    case Sim = 'SIM';
    case Certificate = 'CERTIFICATE';

    public function label(): string
    {
        return match ($this) {
            self::Sim => 'SIM',
            self::Certificate => 'Sertifikat',
        };
    }
}
