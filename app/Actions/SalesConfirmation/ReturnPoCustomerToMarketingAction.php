<?php

namespace App\Actions\SalesConfirmation;

use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReturnPoCustomerToMarketingAction
{
    public function execute(PoCustomer $po, string $updatedBy, string $ip): PoCustomer
    {
        $attachmentPaths = $this->attachmentPaths($po);

        DB::transaction(function () use ($po, $updatedBy, $ip) {
            SalesConfirmation::where('po_customer_id', $po->id_poc)->delete();

            $po->update([
                'sc_process_state' => null,
                'lastupdate_time'  => now(),
                'lastupdate_by'    => $updatedBy,
                'lastupdate_ip'    => $ip,
            ]);
        });

        foreach ($attachmentPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        return $po->refresh();
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
