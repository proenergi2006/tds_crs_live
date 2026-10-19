<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\PoCustomerScProcessState;
use App\Enums\SalesConfirmationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReturnPoCustomerToMarketingAction
{
    public function execute(PoCustomer $po, string $updatedBy, string $ip): PoCustomer
    {
        $attachmentPaths = DB::transaction(function () use ($po, $updatedBy, $ip) {
            $locked = PoCustomer::whereKey($po->getKey())->lockForUpdate()->firstOrFail();

            $this->assertReturnable($locked);

            $paths = $this->attachmentPaths($locked);

            SalesConfirmation::where('po_customer_id', $locked->id_poc)->delete();

            $locked->update([
                'sc_process_state' => null,
                'lastupdate_time'  => now(),
                'lastupdate_by'    => $updatedBy,
                'lastupdate_ip'    => $ip,
            ]);

            return $paths;
        });

        foreach ($attachmentPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        return $po->refresh();
    }

    private function assertReturnable(PoCustomer $po): void
    {
        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            throw new BusinessRuleException('PO Customer belum lolos gerbang Sales Confirmation.');
        }

        $disposisi = SalesConfirmation::where('po_customer_id', $po->id_poc)->lockForUpdate()->first()?->disposisi;

        if ($disposisi === SalesConfirmationStatus::InApproval) {
            throw new BusinessRuleException('Sales Confirmation sedang dalam proses approval.');
        }

        if ($disposisi === SalesConfirmationStatus::Confirmed) {
            throw new BusinessRuleException('Sales Confirmation sudah dikonfirmasi.');
        }
    }

    private function attachmentPaths(PoCustomer $po): array
    {
        $scIds = SalesConfirmation::where('po_customer_id', $po->id_poc)->pluck('id');

        return SalesConfirmationApproval::whereIn('id_sales', $scIds)
            ->pluck('adm_attachments')
            ->flatten(1)
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }
}
