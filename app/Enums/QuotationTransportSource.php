<?php

namespace App\Enums;

enum QuotationTransportSource: string
{
    case TransportTariff = 'transport_tariff';
    case ProductPrice    = 'product_price';
    case Pickup          = 'pickup';

    public function mode(): ?QuotationTransportMode
    {
        return match ($this) {
            self::TransportTariff => QuotationTransportMode::Tariff,
            self::Pickup          => QuotationTransportMode::Pickup,
            self::ProductPrice    => null,
        };
    }
}
