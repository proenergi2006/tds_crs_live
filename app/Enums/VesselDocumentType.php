<?php

namespace App\Enums;

enum VesselDocumentType: string
{
    case ShipParticular = 'SHIP_PARTICULAR';
    case Siopsus = 'SIOPSUS';

    public function label(): string
    {
        return match ($this) {
            self::ShipParticular => 'Ship Particular',
            self::Siopsus => 'SIOPSUS',
        };
    }
}
