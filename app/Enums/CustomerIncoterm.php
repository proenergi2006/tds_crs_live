<?php

namespace App\Enums;

/**
 * Incoterms yang customer declare sendiri di form publik
 * (`customers.inco_terms`), disimpan sebagai string biasa (bukan DB-level
 * enum) -- enum ini source of truth untuk nilai yang valid.
 */
enum CustomerIncoterm: string
{
    case Exw   = 'EXW';
    case Fob   = 'FOB';
    case Cif   = 'CIF';
    case Cfr   = 'CFR';
    case Ddp   = 'DDP';
    case Dap   = 'DAP';
    case Fca   = 'FCA';
    case Cpt   = 'CPT';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Exw   => 'Ex Works',
            self::Fob   => 'Free On Board',
            self::Cif   => 'Cost, Insurance and Freight',
            self::Cfr   => 'Cost and Freight',
            self::Ddp   => 'Delivered Duty Paid',
            self::Dap   => 'Delivered At Place',
            self::Fca   => 'Free Carrier',
            self::Cpt   => 'Carriage Paid To',
            self::Other => 'Other',
        };
    }
}
