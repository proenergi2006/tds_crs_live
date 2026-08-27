<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Enums\DocumentApprovalStepStatus;
use App\Mail\PenawaranApprovedMail;
use App\Mail\PenawaranApprovalRequestMail;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// resolve creator: coba user_id dulu baru created_by -- beda dari RejectPenawaranBmAction, jangan disatukan
class ApprovePenawaranBmAction
{
    use ResolvesApprovalTemplate;

    public function execute(Model $penawaran, ?int $actorId, ?string $catatan, string $actorName, string $detailUrl, string $omVerificationUrl): array
    {
        DB::beginTransaction();

        try {
            $penawaran->update([
                'status'              => 'approved_bm',
                'catatan_verifikasi'  => $catatan,
                'bm_result'           => '1',
                'bm_tanggal'          => now(),
                'approved_at'         => now(),
                'approved_by'         => $actorName,
                'disposisi_penawaran' => 3,
            ]);

            // dual-write: putuskan step 1 (BM) di cycle DocumentApproval bersamaan dengan update kolom lama di atas
            $service = new DocumentApprovalService();
            $templateCode = $this->templateCodeFor($penawaran);
            $service->decideStep($penawaran, 1, DocumentApprovalStepStatus::Approved, $actorId, $catatan);

            $template = $service->activeTemplate($templateCode);

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
                        new PenawaranApprovedMail($penawaran, $detailUrl, 'bm')
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

            $omRoleId = $template?->steps->firstWhere('step_order', 2)?->id_role;

            $approverRecipients = User::role($omRoleId)
                ->whereNotNull('email')
                ->pluck('email')
                ->map(fn ($e) => trim((string) $e))
                ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->all();

            \Log::info('APPROVER RECIPIENTS (OM)', [
                'id_penawaran' => $penawaran->id_penawaran,
                'recipients'   => $approverRecipients,
            ]);

            if (!empty($approverRecipients)) {
                try {
                    Mail::to($approverRecipients)->send(
                        new PenawaranApprovalRequestMail($penawaran, $omVerificationUrl, 'om')
                    );
                } catch (\Throwable $e) {
                    report($e);
                    \Log::error('EMAIL OM/CEO APPROVAL FAILED', [
                        'id_penawaran' => $penawaran->id_penawaran,
                        'recipients'   => $approverRecipients,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return ['message' => 'Penawaran disetujui BM & notifikasi terkirim'];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Gagal verifikasi penawaran',
                'error'   => $e->getMessage(),
            ];
        }
    }
}
