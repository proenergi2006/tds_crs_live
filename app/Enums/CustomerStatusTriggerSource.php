<?php

namespace App\Enums;

/**
 * Asal perubahan status di `customer_status_history.trigger_source`,
 * disimpan sebagai string biasa (bukan DB-level enum) -- enum ini source of
 * truth untuk nilai yang valid.
 */
enum CustomerStatusTriggerSource: string
{
    case System = 'system';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Manual => 'Manual',
        };
    }
}
