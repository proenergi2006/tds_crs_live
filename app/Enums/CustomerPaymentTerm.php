<?php

namespace App\Enums;

/**
 * Preferensi jenis pembayaran yang customer declare sendiri di form publik
 * (`customer_payment.payment_term`), disimpan sebagai string biasa (bukan
 * DB-level enum) -- enum ini source of truth untuk nilai yang valid. Beda
 * level dari `customer_credit_items.top_request/approval` (hasil negosiasi
 * kredit per produk).
 */
enum CustomerPaymentTerm: string
{
    case Cbd    = 'CBD';
    case Cod    = 'COD';
    case Credit = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::Cbd    => 'Cash Before Delivery',
            self::Cod    => 'Cash on Delivery',
            self::Credit => 'Credit',
        };
    }
}
