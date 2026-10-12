<?php

namespace App\Actions\Quotation;

use App\Enums\ProductPriceCogsBasis;
use App\Enums\QuotationTransportMode;
use App\Enums\QuotationTransportSource;
use App\Models\ProductPrice;
use App\Models\TransportTariff;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveQuotationTransportPricingAction
{
    public function execute(array $items, ?string $transportMode, array $transports): array
    {
        $prices = ProductPrice::whereIn('id', array_column($items, 'product_price_id'))->get()->keyBy('id');
        $basis  = $this->resolveBasis($items, $prices);

        $resolved = $basis === ProductPriceCogsBasis::Franco
            ? $this->priceFranco($items, $prices, $transportMode, $transports)
            : $this->priceLoco($items, $transportMode, $transports);

        return [
            'basis'      => $basis,
            'transports' => $resolved['transports'],
            'items'      => $resolved['items'],
        ];
    }

    public function detectBasis(array $items): ?ProductPriceCogsBasis
    {
        $prices = ProductPrice::whereIn('id', array_column($items, 'product_price_id'))->get()->keyBy('id');
        $bases  = [];

        foreach ($items as $item) {
            $basis = ProductPriceCogsBasis::tryFrom((string) $prices->get($item['product_price_id'])?->cogs_basis);

            if ($basis === null) {
                return null;
            }

            $bases[$basis->value] = $basis;
        }

        return count($bases) === 1 ? reset($bases) : null;
    }

    public function assertMetodeMatchesBasis(?ProductPriceCogsBasis $basis, ?string $metode): void
    {
        $isFrancoMetode = strcasecmp(trim((string) $metode), 'FRANCO') === 0;

        if ($basis === ProductPriceCogsBasis::Franco && ! $isFrancoMetode) {
            throw ValidationException::withMessages(['metode' => 'Produk franco harus memakai metode FRANCO.']);
        }

        if ($basis === ProductPriceCogsBasis::Loco && $isFrancoMetode) {
            throw ValidationException::withMessages(['metode' => 'Produk loco tidak boleh memakai metode FRANCO.']);
        }
    }

    private function resolveBasis(array $items, Collection $prices): ProductPriceCogsBasis
    {
        $bases = [];

        foreach (array_values($items) as $index => $item) {
            $basis = $prices->get($item['product_price_id'])?->cogs_basis;

            if ($basis === null || $basis === '' || ProductPriceCogsBasis::tryFrom($basis) === null) {
                $row = $index + 1;

                throw ValidationException::withMessages([
                    'items' => "Produk pada baris ke-{$row} belum memiliki basis harga (loco/franco). Lengkapi di Periode Harga.",
                ]);
            }

            $bases[$basis] = true;
        }

        if (count($bases) > 1) {
            throw ValidationException::withMessages(['items' => 'Penawaran tidak boleh mencampur produk loco dan franco.']);
        }

        return ProductPriceCogsBasis::from(array_key_first($bases));
    }

    private function priceFranco(array $items, Collection $prices, ?string $transportMode, array $transports): array
    {
        if ($transportMode !== null) {
            throw ValidationException::withMessages(['transport_mode' => 'Penawaran franco tidak memakai pilihan angkutan.']);
        }

        if ($transports !== []) {
            throw ValidationException::withMessages(['transports' => 'Penawaran franco tidak boleh memilih tarif angkutan.']);
        }

        $errors   = [];
        $resolved = [];
        $capturedAt = $this->capturedAt();

        foreach ($items as $index => $item) {
            $price          = $prices->get($item['product_price_id']);
            $transportPrice = $price->francoTransportPrice();

            if ($transportPrice === null) {
                $errors["items.{$index}.product_price_id"] = 'Harga transport franco produk ini belum lengkap (COGS atau margin transport belum diisi Logistik).';
                continue;
            }

            $resolved[$index] = [
                'transport_price'    => $this->money($transportPrice),
                'transport_snapshot' => [
                    'source'           => QuotationTransportSource::ProductPrice->value,
                    'product_price_id' => $price->id,
                    'cogs'             => $this->money((float) $price->cogs_transport_price),
                    'margin'           => $this->money((float) $price->margin_transport_amount),
                    'captured_at'      => $capturedAt,
                ],
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['transports' => [], 'items' => $resolved];
    }

    private function priceLoco(array $items, ?string $transportMode, array $transports): array
    {
        if ($transportMode === null) {
            throw ValidationException::withMessages([
                'transport_mode' => 'Pilih moda angkutan (kapal/truck) atau tanpa biaya transport.',
            ]);
        }

        if ($transportMode === QuotationTransportMode::Pickup->value) {
            if ($transports !== []) {
                throw ValidationException::withMessages(['transports' => 'Tanpa biaya transport tidak boleh memilih tarif.']);
            }

            $resolved = [];
            foreach ($items as $index => $item) {
                $resolved[$index] = ['transport_price' => $this->money(0), 'transport_snapshot' => $this->pickupSnapshot()];
            }

            return ['transports' => [], 'items' => $resolved];
        }

        if ($transports === []) {
            throw ValidationException::withMessages(['transports' => 'Pilih minimal satu tarif angkutan.']);
        }

        $types = array_column($transports, 'transport_type');
        if (count($types) !== count(array_unique($types))) {
            throw ValidationException::withMessages(['transports' => 'Setiap moda hanya boleh satu tarif.']);
        }

        $tariffs = TransportTariff::with(['transporter:id,company_name', 'transportArea:id,name', 'volume:id,volume'])
            ->whereIn('id', array_column($transports, 'transport_tariff_id'))
            ->get()
            ->keyBy('id');

        $errors = [];
        $legs   = [];
        $cogs   = 0.0;
        $margin = 0.0;

        foreach (array_values($transports) as $index => $leg) {
            $tariff = $tariffs->get($leg['transport_tariff_id']);
            $key    = "transports.{$index}.transport_tariff_id";

            if ($tariff === null) {
                $errors[$key] = 'Tarif angkutan tidak ditemukan.';
            } elseif ($tariff->transport_type->value !== $leg['transport_type']) {
                $errors[$key] = 'Tarif ini bukan untuk moda yang dipilih.';
            } elseif (! $tariff->is_active) {
                $errors[$key] = 'Tarif angkutan ini sudah nonaktif.';
            } elseif ($tariff->margin === null) {
                $errors[$key] = 'Margin tarif ini belum diisi Logistik.';
            }

            if (isset($errors[$key])) {
                continue;
            }

            $cogs   += (float) $tariff->rate;
            $margin += (float) $tariff->margin;
            $legs[]  = [
                'transport_tariff_id' => $tariff->id,
                'transport_type'      => $tariff->transport_type->value,
                'transporter'         => $tariff->transporter?->company_name,
                'transport_area'      => $tariff->transportArea?->name,
                'volume'              => $tariff->volume?->volume !== null ? (string) $tariff->volume->volume : null,
                'cogs'                => $this->money((float) $tariff->rate),
                'margin'              => $this->money((float) $tariff->margin),
                'price'               => $tariff->sellingPrice(),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $snapshot = [
            'source'      => QuotationTransportSource::TransportTariff->value,
            'cogs'        => $this->money($cogs),
            'margin'      => $this->money($margin),
            'transports'  => $legs,
            'captured_at' => $this->capturedAt(),
        ];
        $transportPrice = $this->money($cogs + $margin);

        $resolved = [];
        foreach ($items as $index => $item) {
            $resolved[$index] = ['transport_price' => $transportPrice, 'transport_snapshot' => $snapshot];
        }

        return [
            'transports' => array_map(fn (array $leg) => [
                'transport_type'      => $leg['transport_type'],
                'transport_tariff_id' => (int) $leg['transport_tariff_id'],
            ], $legs),
            'items' => $resolved,
        ];
    }

    private function pickupSnapshot(): array
    {
        return [
            'source'      => QuotationTransportSource::Pickup->value,
            'cogs'        => $this->money(0),
            'margin'      => $this->money(0),
            'transports'  => [],
            'captured_at' => $this->capturedAt(),
        ];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function capturedAt(): string
    {
        return now()->toIso8601String();
    }
}
