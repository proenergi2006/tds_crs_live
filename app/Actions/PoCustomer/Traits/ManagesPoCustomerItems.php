<?php

namespace App\Actions\PoCustomer\Traits;

use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

trait ManagesPoCustomerItems
{
    private function payloadVolumes(array $payloadItems): array
    {
        $volumes = [];

        foreach ($payloadItems as $payloadItem) {
            $volumes[(int) $payloadItem['quotation_item_id']] = (string) $payloadItem['volume'];
        }

        return $volumes;
    }

    private function assertItemInvariants(
        Collection $quotationItems,
        array $payloadItems,
        array $storedVolumes = []
    ): void {
        $volumes    = $this->payloadVolumes($payloadItems);
        $totalCents = 0;

        foreach ($volumes as $quotationItemId => $volume) {
            $quotationItem = $quotationItems->firstWhere('id', $quotationItemId);

            if ($quotationItem === null) {
                throw new BusinessRuleException('Item tidak termasuk dalam penawaran ini.', 422);
            }

            $volumeCents = $this->volumeToCents($volume);
            $limitCents  = max(
                $this->volumeToCents($quotationItem->quantity),
                $this->volumeToCents($storedVolumes[$quotationItemId] ?? 0)
            );

            if ($volumeCents > $limitCents) {
                $quantityLabel = rtrim(rtrim(number_format((float) $quotationItem->quantity, 2, ',', '.'), '0'), ',');

                throw new BusinessRuleException("Volume melebihi kuantitas penawaran ({$quantityLabel}).", 422);
            }

            $totalCents += $volumeCents;
        }

        if ($totalCents === 0) {
            throw new BusinessRuleException('Minimal satu item harus memiliki volume lebih dari 0.', 422);
        }
    }

    private function createItems(PoCustomer $po, Collection $quotationItems, array $payloadItems): void
    {
        $volumes = $this->payloadVolumes($payloadItems);

        foreach ($quotationItems as $quotationItem) {
            $po->items()->create([
                'quotation_item_id' => $quotationItem->id,
                'volume'            => $volumes[$quotationItem->id] ?? '0',
                'unit_price'        => $quotationItem->unit_price,
                'transport_price'   => $quotationItem->transport_price ?? '0',
            ]);
        }
    }

    private function updateItemVolumes(PoCustomer $po, array $payloadItems): void
    {
        $volumes = $this->payloadVolumes($payloadItems);

        foreach ($po->items()->get() as $item) {
            $item->update(['volume' => $volumes[$item->quotation_item_id] ?? '0']);
        }
    }

    private function syncVolumePoc(PoCustomer $po): void
    {
        $po->forceFill(['volume_poc' => $po->items()->sum('volume')])->save();
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $filename = time() . '_' . $file->getClientOriginalName();

        return [
            'lampiran_poc'     => $file->storeAs('lampiran_po', $filename, 'public'),
            'lampiran_poc_ori' => $file->getClientOriginalName(),
        ];
    }

    private function translateDuplicateQuotation(\Throwable $e): \Throwable
    {
        $isDuplicateQuotation = $e instanceof UniqueConstraintViolationException
            && str_contains($e->getMessage(), 'po_customers_quotation_id_unique');

        if ($isDuplicateQuotation) {
            return new BusinessRuleException('Penawaran ini sudah memiliki PO Customer.', 409, $e);
        }

        return $e;
    }

    private function volumeToCents(string|int|float $volume): int
    {
        return (int) round((float) $volume * 100);
    }
}
