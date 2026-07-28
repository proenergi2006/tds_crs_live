<?php

namespace App\Enums;

/**
 * Acuan hitung hari `customer_payment.payment_term_days` -- relevan hanya
 * saat `payment_term = CREDIT`. Disimpan sebagai string biasa (bukan
 * DB-level enum) -- enum ini source of truth untuk nilai yang valid.
 */
enum CustomerPaymentTermBasis: string
{
    case DaysAfterDelivery       = 'days_after_delivery';
    case DaysAfterInvoiceReceived = 'days_after_invoice_received';

    public function label(): string
    {
        return match ($this) {
            self::DaysAfterDelivery        => 'Days After Delivery',
            self::DaysAfterInvoiceReceived => 'Days After Invoice Received',
        };
    }
}
