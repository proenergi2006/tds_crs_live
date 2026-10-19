<?php

namespace App\Actions\PoCustomer;

use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeletePoCustomerAction
{
    public function execute(PoCustomer $po): void
    {
        $attachmentPath = DB::transaction(function () use ($po) {
            $locked = PoCustomer::whereKey($po->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->is_locked) {
                throw new BusinessRuleException(
                    'PO Customer tidak bisa dihapus karena sudah masuk proses Sales Confirmation.'
                );
            }

            $locked->delete();

            return $locked->lampiran_poc;
        });

        if ($attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
        }
    }
}
