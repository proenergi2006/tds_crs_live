<?php

namespace App\Actions\PoCustomer;

use App\Actions\PoCustomer\Traits\ManagesPoCustomerItems;
use App\Enums\PoCustomerScProcessState;
use App\Enums\QuotationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use App\Models\Quotation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePoCustomerAction
{
    use ManagesPoCustomerItems;

    public function execute(
        PoCustomer $po,
        array $validated,
        ?UploadedFile $lampiran,
        string $updatedBy,
        string $ip
    ): PoCustomer {
        $newAttachment = $lampiran ? $this->storeAttachment($lampiran) : null;
        $oldPath       = null;

        try {
            $updated = DB::transaction(function () use ($po, $validated, $newAttachment, $updatedBy, $ip, &$oldPath) {
                $locked = PoCustomer::whereKey($po->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->is_locked) {
                    throw new BusinessRuleException('PO Customer sudah terkunci dan tidak bisa diubah.');
                }

                $data = Arr::except($validated, ['lampiran_poc', 'items', 'quotation_id']);

                $data['termin_hari'] = $validated['tipe_bayar'] === 'CREDIT' ? $validated['termin_hari'] : null;

                $targetId         = (int) ($validated['quotation_id'] ?? $locked->quotation_id);
                $quotationChanged = $targetId !== (int) $locked->quotation_id;
                $replacement      = $quotationChanged ? $this->lockReplacement($targetId) : null;
                $quotationItems   = ($replacement ?? $locked->quotation)->items()->orderBy('id')->get();

                $this->assertItemInvariants(
                    $quotationItems,
                    $validated['items'],
                    $quotationChanged ? [] : $locked->items()->pluck('volume', 'quotation_item_id')->all()
                );

                if ($quotationChanged) {
                    $data['quotation_id'] = $targetId;
                    $data['ppn_rate']     = $replacement->ppn_rate;
                    $data['harga_poc']    = null;
                    $data['produk_poc']   = null;
                }

                if ($newAttachment) {
                    $oldPath = $locked->lampiran_poc;
                    $data    = [...$data, ...$newAttachment];
                }

                if ($locked->sc_process_state === PoCustomerScProcessState::Blocked) {
                    $data['sc_process_state'] = null;
                }

                $data['lastupdate_time'] = now();
                $data['lastupdate_by']   = $updatedBy;
                $data['lastupdate_ip']   = $ip;

                $locked->update($data);

                if ($quotationChanged) {
                    $locked->items()->delete();
                    $this->createItems($locked, $quotationItems, $validated['items']);
                } else {
                    $this->updateItemVolumes($locked, $validated['items']);
                }

                $this->syncVolumePoc($locked);

                return $locked->refresh();
            });
        } catch (\Throwable $e) {
            if ($newAttachment) {
                Storage::disk('public')->delete($newAttachment['lampiran_poc']);
            }

            throw $e;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updated;
    }

    private function lockReplacement(int $quotationId): Quotation
    {
        $quotation = Quotation::whereKey($quotationId)->lockForUpdate()->firstOrFail();

        if ($quotation->status !== QuotationStatus::Approved) {
            throw new BusinessRuleException('Penawaran ini sudah tidak berstatus disetujui.');
        }

        return $quotation;
    }
}
