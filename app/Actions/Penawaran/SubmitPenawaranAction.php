<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Mail\PenawaranApprovalRequestMail;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// model-agnostic, dipanggil buat Penawaran (TDS) & PenawaranProenergi -- kolomnya sama walau beda class
class SubmitPenawaranAction
{
    use ResolvesApprovalTemplate;

    public function execute(Model $penawaran, string $bmVerificationUrl): array
    {
        if ($penawaran->status !== 'draft') {
            return [
                'success' => false,
                'status'  => 400,
                'message' => 'Penawaran sudah diajukan sebelumnya',
            ];
        }

        DB::beginTransaction();

        try {
            $penawaran->update([
                'status'              => 'waiting_branch_manager',
                'disposisi_penawaran' => '2',
                'updated_at'          => now(),
            ]);

            // dual-write: mulai cycle DocumentApproval bersamaan dengan update kolom lama di atas
            $service = new DocumentApprovalService();
            $templateCode = $this->templateCodeFor($penawaran);
            $service->startCycle($penawaran, $templateCode);

            $template = $service->activeTemplate($templateCode);
            $bmRoleId = $template?->steps->firstWhere('step_order', 1)?->id_role;

            $recipients = User::query()
                ->where('id_role', $bmRoleId)
                ->whereNotNull('email')
                ->pluck('email')
                ->map(fn ($e) => trim((string) $e))
                ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->all();

            Log::info('AJUKAN PENAWARAN - BM RECIPIENTS', [
                'id_penawaran' => $penawaran->id_penawaran,
                'nomor'        => $penawaran->nomor_penawaran,
                'recipients'   => $recipients,
            ]);

            if (empty($recipients)) {
                DB::commit();

                return [
                    'message' => 'Penawaran berhasil diajukan, tetapi email BM tidak ditemukan.',
                ];
            }

            try {
                Mail::to($recipients)->send(new PenawaranApprovalRequestMail($penawaran, $bmVerificationUrl, 'bm'));
            } catch (\Throwable $mailErr) {
                DB::rollBack();

                Log::error('AJUKAN PENAWARAN - GAGAL KIRIM EMAIL BM', [
                    'id_penawaran' => $penawaran->id_penawaran,
                    'error'        => $mailErr->getMessage(),
                    'trace'        => $mailErr->getTraceAsString(),
                ]);

                return [
                    'success' => false,
                    'status'  => 500,
                    'message' => 'Penawaran gagal diajukan karena email BM gagal dikirim.',
                    'error'   => $mailErr->getMessage(),
                ];
            }

            DB::commit();

            return [
                'message' => 'Penawaran berhasil diajukan dan email berhasil dikirim ke Branch Manager.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('AJUKAN PENAWARAN - ERROR', [
                'id_penawaran' => $penawaran->id_penawaran ?? null,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Gagal mengajukan penawaran',
                'error'   => $e->getMessage(),
            ];
        }
    }
}
