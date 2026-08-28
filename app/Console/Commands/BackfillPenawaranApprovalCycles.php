<?php

namespace App\Console\Commands;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplate;
use App\Models\Penawaran;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BackfillPenawaranApprovalCycles extends Command
{
    protected $signature = 'penawaran:backfill-approval-cycles';

    protected $description = 'Backfill document_approvals/document_approval_steps dari kolom status lama Penawaran TDS & Proenergi (idempotent, one-time).';

    public function handle(): int
    {
        $resultTds = $this->backfillBrand('tds', 'penawaran_tds');
        $this->info("[penawaran_tds] processed: {$resultTds['created']}, skipped (existing cycle): {$resultTds['skipped_existing']}, skipped (unknown status): {$resultTds['skipped_unknown']}");

        $resultProenergi = $this->backfillBrand('proenergi', 'penawaran_proenergi');
        $this->info("[penawaran_proenergi] processed: {$resultProenergi['created']}, skipped (existing cycle): {$resultProenergi['skipped_existing']}, skipped (unknown status): {$resultProenergi['skipped_unknown']}");

        return self::SUCCESS;
    }

    private function backfillBrand(string $brand, string $templateCode): array
    {
        $template = ApprovalTemplate::where('code', $templateCode)->with('steps')->firstOrFail();

        $counts = ['created' => 0, 'skipped_existing' => 0, 'skipped_unknown' => 0];

        $penawarans = Penawaran::where('brand', $brand)->where('status', '!=', 'draft')->get();

        foreach ($penawarans as $penawaran) {
            DB::transaction(function () use ($penawaran, $template, &$counts) {
                $result = $this->backfillRecord($penawaran, $template);
                $key = $result === 'skipped_unknown_status' ? 'skipped_unknown' : $result;
                $counts[$key]++;
            });
        }

        return $counts;
    }

    private function backfillRecord(Model $penawaran, ApprovalTemplate $template): string
    {
        if ($penawaran->documentApprovals()->exists()) {
            return 'skipped_existing';
        }

        $stepBm = $template->steps->firstWhere('step_order', 1);
        $stepOm = $template->steps->firstWhere('step_order', 2);

        switch ($penawaran->status) {
            case 'waiting_branch_manager':
                $approval = $penawaran->documentApprovals()->create([
                    'id_template' => $template->id_template,
                    'status' => DocumentApprovalStatus::InProgress,
                    'current_step_order' => 1,
                    'started_at' => $penawaran->created_at,
                ]);

                foreach ($template->steps as $step) {
                    $approval->steps()->create([
                        'id_template_step' => $step->id_step,
                        'step_order' => $step->step_order,
                        'status' => DocumentApprovalStepStatus::Pending,
                        'decision_note' => '[migrasi]',
                    ]);
                }

                return 'created';

            case 'approved_om':
                $completedAt = $penawaran->om_tanggal ?? $penawaran->bm_tanggal ?? $penawaran->created_at;

                $approval = $penawaran->documentApprovals()->create([
                    'id_template' => $template->id_template,
                    'status' => DocumentApprovalStatus::Approved,
                    'current_step_order' => null,
                    'started_at' => $penawaran->created_at,
                    'completed_at' => $completedAt,
                ]);

                $omActedAt = ($penawaran->om_tanggal && $penawaran->om_tanggal >= $penawaran->bm_tanggal)
                    ? $penawaran->om_tanggal
                    : $approval->completed_at;

                $approval->steps()->create([
                    'id_template_step' => $stepBm->id_step,
                    'step_order' => $stepBm->step_order,
                    'status' => DocumentApprovalStepStatus::Approved,
                    'acted_at' => $penawaran->bm_tanggal ?? $penawaran->created_at,
                    'decision_note' => '[migrasi] ' . trim((string) ($penawaran->catatan_verifikasi ?? '')),
                ]);

                $approval->steps()->create([
                    'id_template_step' => $stepOm->id_step,
                    'step_order' => $stepOm->step_order,
                    'status' => DocumentApprovalStepStatus::Approved,
                    'acted_at' => $omActedAt,
                    'decision_note' => '[migrasi] ' . trim((string) ($penawaran->catatan_om ?? '')),
                ]);

                return 'created';

            case 'rejected_bm':
                $approval = $penawaran->documentApprovals()->create([
                    'id_template' => $template->id_template,
                    'status' => DocumentApprovalStatus::Rejected,
                    'current_step_order' => null,
                    'started_at' => $penawaran->created_at,
                    'completed_at' => $penawaran->bm_tanggal ?? $penawaran->created_at,
                ]);

                $approval->steps()->create([
                    'id_template_step' => $stepBm->id_step,
                    'step_order' => $stepBm->step_order,
                    'status' => DocumentApprovalStepStatus::Rejected,
                    'acted_at' => $penawaran->bm_tanggal ?? $penawaran->created_at,
                    'decision_note' => '[migrasi] ' . trim((string) ($penawaran->catatan_verifikasi ?? '')),
                ]);

                return 'created';

            case 'rejected_om':
                $approval = $penawaran->documentApprovals()->create([
                    'id_template' => $template->id_template,
                    'status' => DocumentApprovalStatus::Rejected,
                    'current_step_order' => null,
                    'started_at' => $penawaran->created_at,
                    'completed_at' => $penawaran->om_tanggal ?? $penawaran->created_at,
                ]);

                $approval->steps()->create([
                    'id_template_step' => $stepBm->id_step,
                    'step_order' => $stepBm->step_order,
                    'status' => DocumentApprovalStepStatus::Approved,
                    'acted_at' => $penawaran->bm_tanggal ?? $penawaran->created_at,
                    'decision_note' => '[migrasi] ' . trim((string) ($penawaran->catatan_verifikasi ?? '')),
                ]);

                $approval->steps()->create([
                    'id_template_step' => $stepOm->id_step,
                    'step_order' => $stepOm->step_order,
                    'status' => DocumentApprovalStepStatus::Rejected,
                    'acted_at' => $penawaran->om_tanggal ?? $penawaran->created_at,
                    'decision_note' => '[migrasi] ' . trim((string) ($penawaran->catatan_om ?? '')),
                ]);

                return 'created';

            default:
                return 'skipped_unknown_status';
        }
    }
}
