<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Enums\DocumentApprovalStepStatus;
use App\Mail\PenawaranRejectedMail;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RejectPenawaranBmAction
{
    use ResolvesApprovalTemplate;

    public function execute(Model $penawaran, ?int $actorId, ?string $catatan, string $detailUrl): array
    {
        DB::beginTransaction();

        try {
            $penawaran->update([
                'status'              => 'rejected_bm',
                'disposisi_penawaran' => 5,
            ]);

            $service = new DocumentApprovalService();
            $templateCode = $this->templateCodeFor($penawaran);
            $service->decideStep($penawaran, 1, DocumentApprovalStepStatus::Rejected, $actorId, $catatan);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return ['success' => false, 'status' => 500, 'message' => 'Gagal menolak penawaran', 'error' => $e->getMessage()];
        }

        $creatorEmail = $this->getCreatorEmail($penawaran->created_by);

        if ($creatorEmail) {
            try {
                Mail::to($creatorEmail)->send(
                    new PenawaranRejectedMail($penawaran, $detailUrl, 'Ditolak oleh Branch Manager', $catatan)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ['message' => 'Status penawaran Ditolak'];
    }

    private function getCreatorEmail(?string $creatorName): ?string
    {
        if (!$creatorName) {
            return null;
        }

        $query = User::where('name', $creatorName)->whereNotNull('email');

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->first()?->email;
    }
}
