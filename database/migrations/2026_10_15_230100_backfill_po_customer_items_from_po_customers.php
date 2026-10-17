<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertItemsTableIsEmpty();

        $now = now();

        $poCustomers = DB::select(
            'SELECT id_poc, quotation_id, ROUND(volume_poc * 100)::bigint AS volume_cents FROM po_customers ORDER BY id_poc'
        );

        $itemsByQuotation = collect(DB::select(
            'SELECT qi.id, qi.quotation_id, ROUND(qi.quantity * 100)::bigint AS quantity_cents, '
            . 'qi.unit_price::text AS unit_price, qi.transport_price::text AS transport_price '
            . 'FROM quotation_items qi '
            . 'WHERE qi.quotation_id IN (SELECT quotation_id FROM po_customers) '
            . 'ORDER BY qi.quotation_id, qi.id'
        ))->groupBy('quotation_id');

        $rows = [];
        $withoutItems = [];
        $zeroQuantity = [];
        $nonPositiveVolume = [];

        foreach ($poCustomers as $poCustomer) {
            $quotationItems = $itemsByQuotation->get($poCustomer->quotation_id, collect());

            if ($quotationItems->isEmpty()) {
                $withoutItems[] = $poCustomer->id_poc;
                continue;
            }

            if ($quotationItems->sum('quantity_cents') <= 0) {
                $zeroQuantity[] = $poCustomer->id_poc;
                continue;
            }

            if ((int) $poCustomer->volume_cents <= 0) {
                $nonPositiveVolume[] = $poCustomer->id_poc;
                continue;
            }

            foreach ($this->backfillPoCustomer($poCustomer, $quotationItems) as $row) {
                $rows[] = $row + ['created_at' => $now, 'updated_at' => $now];
            }
        }

        if ($withoutItems !== [] || $zeroQuantity !== [] || $nonPositiveVolume !== []) {
            throw new RuntimeException(sprintf(
                'Cannot backfill po_customer_items. Quotation without items: [%s]. Quotation with zero total quantity: [%s]. PO with volume_poc <= 0: [%s].',
                implode(', ', $withoutItems),
                implode(', ', $zeroQuantity),
                implode(', ', $nonPositiveVolume)
            ));
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('po_customer_items')->insert($chunk);
        }

        $this->assertTotalsMatchLegacyPrice();
    }

    public function down(): void
    {
        DB::table('po_customer_items')->delete();
    }

    private function assertItemsTableIsEmpty(): void
    {
        if (DB::table('po_customer_items')->exists()) {
            throw new RuntimeException('po_customer_items is not empty; refusing to backfill twice.');
        }
    }

    private function backfillPoCustomer(object $poCustomer, Collection $quotationItems): array
    {
        $quantityCentsByItemId = [];

        foreach ($quotationItems as $item) {
            $quantityCentsByItemId[(int) $item->id] = (int) $item->quantity_cents;
        }

        $volumeCentsByItemId = $this->distributeVolumeCents((int) $poCustomer->volume_cents, $quantityCentsByItemId);

        $rows = [];

        foreach ($quotationItems as $item) {
            $rows[] = [
                'po_customer_id' => $poCustomer->id_poc,
                'quotation_item_id' => $item->id,
                'volume' => $this->formatCents($volumeCentsByItemId[(int) $item->id]),
                'unit_price' => $item->unit_price,
                'transport_price' => $item->transport_price,
            ];
        }

        return $rows;
    }

    private function distributeVolumeCents(int $totalCents, array $quantityCentsByItemId): array
    {
        $sumCents = array_sum($quantityCentsByItemId);

        $base = [];
        $remainders = [];

        foreach ($quantityCentsByItemId as $itemId => $quantityCents) {
            if ($totalCents > 0 && $quantityCents > intdiv(PHP_INT_MAX, $totalCents)) {
                throw new RuntimeException("Volume distribution overflows integer range for quotation item {$itemId}.");
            }

            $numerator = $totalCents * $quantityCents;
            $base[$itemId] = intdiv($numerator, $sumCents);
            $remainders[$itemId] = $numerator % $sumCents;
        }

        $leftover = $totalCents - array_sum($base);

        $order = array_keys($quantityCentsByItemId);
        usort($order, fn ($a, $b) => [$remainders[$b], $a] <=> [$remainders[$a], $b]);

        foreach (array_slice($order, 0, $leftover) as $itemId) {
            $base[$itemId]++;
        }

        if (array_sum($base) !== $totalCents) {
            throw new RuntimeException('Volume distribution does not add up to the original volume.');
        }

        return $base;
    }

    private function formatCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function assertTotalsMatchLegacyPrice(): void
    {
        $mismatches = DB::select(
            'SELECT p.id_poc, '
            . 'ROUND(p.harga_poc * p.volume_poc, 2) AS legacy_total, '
            . 'COALESCE(SUM(ROUND(i.volume * (i.unit_price + i.transport_price), 2)), 0) AS items_total '
            . 'FROM po_customers p '
            . 'LEFT JOIN po_customer_items i ON i.po_customer_id = p.id_poc '
            . 'GROUP BY p.id_poc, p.harga_poc, p.volume_poc '
            . 'HAVING ABS(ROUND(p.harga_poc * p.volume_poc, 2) - COALESCE(SUM(ROUND(i.volume * (i.unit_price + i.transport_price), 2)), 0)) > 1 '
            . 'ORDER BY p.id_poc'
        );

        if ($mismatches === []) {
            return;
        }

        $lines = array_map(
            fn ($row) => "id_poc {$row->id_poc}: legacy_total {$row->legacy_total}, items_total {$row->items_total}",
            $mismatches
        );

        throw new RuntimeException('Backfilled item totals differ from legacy price by more than 1: ' . implode('; ', $lines));
    }
};
