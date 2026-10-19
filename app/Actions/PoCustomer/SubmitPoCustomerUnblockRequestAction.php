<?php

namespace App\Actions\PoCustomer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\PoCustomerScProcessState;
use App\Exceptions\BusinessRuleException;
use App\Models\PoCustomer;
use App\Models\PoCustomerUnblockRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmitPoCustomerUnblockRequestAction
{
    public function execute(PoCustomer $po, array $data, array $files, int $userId, string $pic, ?string $ip): PoCustomerUnblockRequest
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($po, $data, $files, $userId, $pic, $ip, &$stored) {
                $locked = PoCustomer::whereKey($po->getKey())->lockForUpdate()->firstOrFail();

                $this->assertCanRequestUnblock($locked);

                $this->storeFiles($locked, $files, $stored);

                $unblockRequest = PoCustomerUnblockRequest::create([
                    'id_poc'       => $locked->id_poc,
                    'reason'       => $data['reason'] ?? null,
                    'attachments'  => $stored,
                    'status'       => DocumentApprovalStatus::Approved,
                    'requested_by' => $userId,
                ]);

                $locked->update([
                    'sc_process_state' => PoCustomerScProcessState::Cleared,
                    'lastupdate_time'  => now(),
                    'lastupdate_ip'    => $ip,
                    'lastupdate_by'    => $pic,
                ]);

                return $unblockRequest;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }

            throw $e;
        }
    }

    private function assertCanRequestUnblock(PoCustomer $po): void
    {
        if ($po->sc_process_state !== PoCustomerScProcessState::Blocked) {
            throw new BusinessRuleException('Unblock hanya untuk PO yang diblokir kredit.');
        }

        if ($po->activeUnblockRequest() !== null || $po->approvedUnblockRequest() !== null) {
            throw new BusinessRuleException('Sudah ada pengajuan Unblock aktif / disetujui untuk PO ini.');
        }
    }

    private function storeFiles(PoCustomer $po, array $files, array &$stored): void
    {
        foreach ($files as $file) {
            $path = $file->storeAs(
                "unblock-requests/{$po->id_poc}",
                Str::random(20) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $stored[] = ['path' => $path, 'original_name' => $file->getClientOriginalName()];
        }
    }
}
