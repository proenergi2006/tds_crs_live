<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Enums\DocumentApprovalStepStatus;
use App\Mail\PenawaranApprovedMail;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ApprovePenawaranOmAction
{
    use ResolvesApprovalTemplate;

    public function execute(Model $penawaran, ?int $actorId, ?string $catatan, string $detailUrl): array
    {
        DB::beginTransaction();

        try {
            $penawaran->update([
                'status'              => 'approved_om',
                'disposisi_penawaran' => 4,
                'token_verifikasi'    => strtoupper(Str::random(17)),
            ]);

            $service = new DocumentApprovalService();
            $templateCode = $this->templateCodeFor($penawaran);
            $service->decideStep($penawaran, 2, DocumentApprovalStepStatus::Approved, $actorId, $catatan);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return ['success' => false, 'status' => 500, 'message' => 'Gagal menyetujui penawaran', 'error' => $e->getMessage()];
        }

        $creatorEmail = null;

        if (!empty($penawaran->user_id)) {
            $creatorEmail = User::where('id', $penawaran->user_id)
                ->whereNotNull('email')
                ->value('email');
        }

        if (!$creatorEmail && !empty($penawaran->created_by)) {
            $creatorEmail = User::where('name', $penawaran->created_by)
                ->whereNotNull('email')
                ->value('email');
        }

        if ($creatorEmail) {
            try {
                Mail::to($creatorEmail)->send(
                    new PenawaranApprovedMail($penawaran, $detailUrl, 'om')
                );
            } catch (\Throwable $e) {
                report($e);
                \Log::error('EMAIL CREATOR UPDATE FAILED', [
                    'id_penawaran' => $penawaran->id_penawaran,
                    'email'        => $creatorEmail,
                    'error'        => $e->getMessage(),
                ]);
            }
        } else {
            \Log::warning('CREATOR EMAIL NOT FOUND', [
                'id_penawaran' => $penawaran->id_penawaran,
                'user_id'      => $penawaran->user_id,
                'created_by'   => $penawaran->created_by,
            ]);
        }

        return ['message' => 'Status penawaran diperbarui oleh OM'];
    }
}
