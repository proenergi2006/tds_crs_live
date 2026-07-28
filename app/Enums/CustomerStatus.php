<?php

namespace App\Enums;

/**
 * Status lifecycle customer (`customers.customer_status`), disimpan sebagai
 * string biasa (bukan DB-level enum) -- enum ini source of truth untuk nilai
 * yang valid.
 */
enum CustomerStatus: string
{
    case Prospect = 'prospect';
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
