<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PenawaranDisposisi;
use App\Mail\PenawaranApprovedMail;
use App\Mail\PenawaranApprovalRequestMail;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ApprovePenawaranBmAction
{
    use ResolvesApprovalTemplate;

    public function execute(Model $penawaran, ?int $actorId, ?string $catatan, string $actorName, string $detailUrl, string $omVerificationUrl): array
    {
        DB::beginTransaction();

        try {
            $service = new DocumentApprovalService();
            $templateCode = $this->templateCodeFor($penawaran);
            $cycle = $service->decideStep($penawaran, 1, DocumentApprovalStepStatus::Approved, $actorId, $catatan);
            $isCycleFinal = $cycle?->status === DocumentApprovalStatus::Approved;

            if ($isCycleFinal) {
                $penawaran->update([
                    'status'              => 'approved_om',
                    'disposisi_penawaran' => PenawaranDisposisi::DisetujuiOm,
                    'token_verifikasi'    => strtoupper(Str::random(17)),
                ]);
            } else {
                $penawaran->update([
                    'status'              => 'approved_bm',
                    'disposisi_penawaran' => PenawaranDisposisi::MenungguVerifikasiOm,
                ]);
            }

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

            if (!$isCycleFinal) {
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
            }

            DB::commit();

            return ['message' => $isCycleFinal
                ? 'Penawaran disetujui BM & langsung final'
                : 'Penawaran disetujui BM & notifikasi terkirim'];
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
