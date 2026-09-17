<?php

namespace App\Actions\PoCustomer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\PoCustomerScProcessState;
use App\Models\PoCustomer;
use App\Models\PoCustomerUnblockRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmitPoCustomerUnblockRequestAction
{
    public function execute(PoCustomer $po, array $data, array $files, int $userId, string $pic, ?string $ip): PoCustomerUnblockRequest
    {
        $stored = [];

        foreach ($files as $file) {
            $path = $file->storeAs(
                "unblock-requests/{$po->id_poc}",
                Str::random(20) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $stored[] = ['path' => $path, 'original_name' => $file->getClientOriginalName()];
        }

        return DB::transaction(function () use ($po, $data, $stored, $userId, $pic, $ip) {
            $unblockRequest = PoCustomerUnblockRequest::create([
                'id_poc'       => $po->id_poc,
                'reason'       => $data['reason'] ?? null,
                'attachments'  => $stored,
                'status'       => DocumentApprovalStatus::Approved,
                'requested_by' => $userId,
            ]);

            $po->update([
                'sc_process_state' => PoCustomerScProcessState::Cleared,
                'lastupdate_time'  => now(),
                'lastupdate_ip'    => $ip,
                'lastupdate_by'    => $pic,
            ]);

            return $unblockRequest;
        });
    }
}
