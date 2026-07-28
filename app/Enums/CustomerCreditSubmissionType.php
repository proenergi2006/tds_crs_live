<?php

namespace App\Enums;

/**
 * Jenis pengajuan di `customer_credit_submissions`. Kolom `submission_type`
 * disimpan sebagai string biasa (bukan DB-level enum) -- enum ini source of
 * truth untuk nilai yang valid; menambah jenis baru cukup edit file ini,
 * tanpa migration baru.
 */
enum CustomerCreditSubmissionType: string
{
    case NewCustomer     = 'new_customer';
    case ReActivated     = 're_activated';
    case AddTop          = 'add_top';
    case AddCreditLimit  = 'add_credit_limit';

    public function label(): string
    {
        return match ($this) {
            self::NewCustomer    => 'Customer Baru',
            self::ReActivated    => 'Reaktivasi Customer',
            self::AddTop         => 'Penambahan TOP',
            self::AddCreditLimit => 'Penambahan Limit Kredit',
        };
    }
}
