<?php

namespace App\Actions\PoCustomer;

use App\Actions\PoCustomer\Traits\ManagesPoCustomerItems;
use App\Enums\QuotationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreatePoCustomerAction
{
    use ManagesPoCustomerItems;

    public function execute(array $validated, ?UploadedFile $lampiran, User $user, string $ip): PoCustomer
    {
        $attachment = $lampiran ? $this->storeAttachment($lampiran) : ['lampiran_poc' => '', 'lampiran_poc_ori' => ''];

        try {
            return DB::transaction(function () use ($validated, $attachment, $user, $ip) {
                $quotation = Quotation::whereKey($validated['quotation_id'])->lockForUpdate()->firstOrFail();

                if ($quotation->status !== QuotationStatus::Approved) {
                    throw new BusinessRuleException('Penawaran ini sudah tidak berstatus disetujui.');
                }

                $quotationItems = $quotation->items()->orderBy('id')->get();
                $this->assertItemInvariants($quotationItems, $validated['items']);

                $po = PoCustomer::create([
                    ...Arr::only($validated, [
                        'id_customer',
                        'quotation_id',
                        'nomor_poc',
                        'tanggal_poc',
                        'supply_date',
                        'tipe_bayar'
                    ]),
                    'ppn_rate'     => $quotation->ppn_rate,
                    'termin_hari'  => $validated['tipe_bayar'] === 'CREDIT' ? $validated['termin_hari'] : null,
                    'created_time' => now(),
                    'created_by'   => $user->name ?? 'system',
                    'created_ip'   => $ip,
                    ...$attachment,
                ]);

                $this->createItems($po, $quotationItems, $validated['items']);
                $this->syncVolumePoc($po);

                return $po->refresh();
            });
        } catch (\Throwable $e) {
            if ($lampiran) {
                Storage::disk('public')->delete($attachment['lampiran_poc']);
            }

            throw $e;
        }
    }
}
