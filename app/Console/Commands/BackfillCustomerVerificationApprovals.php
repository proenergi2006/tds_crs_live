<?php

namespace App\Console\Commands;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplate;
use App\Models\CustomerVerification;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill data lama `customer_verifications` (disposisi_result / sm_result /
 * om_result / is_approved) ke tabel sistem approval generik baru
 * (`document_approvals` / `document_approval_steps`), memakai template
 * `customer_verification` (pivot 2026-07-10: 2 step final -- Admin Finance ->
 * BM, Marketing bukan lagi step formal) yang sudah di-seed/dikoreksi di
 * Prioritas A / CA1.
 *
 * Dry-run secara default -- tidak menulis apapun ke DB kecuali flag --commit
 * diberikan. Idempotent: baris yang sudah punya `document_approvals`
 * (dicek lewat relasi `CustomerVerification::documentApprovals()`) dilewati,
 * aman dijalankan berulang kali.
 *
 * Mapping (CA2, menggantikan mapping 3-step Prioritas B1 -- lihat
 * .claude/plans/approval-system-customer-verification.md Prioritas C-Amend):
 * - disposisi_result=0            -> TIDAK eligible untuk cycle sama sekali
 *                                    di model baru (verification yang belum
 *                                    pernah di-forward Marketing tidak punya
 *                                    document_approvals). Dilewati, bukan
 *                                    dibuatkan cycle pending.
 * - disposisi_result=1            -> sudah lolos Marketing (di model lama),
 *                                    treat sebagai sudah di-forward: step 1
 *                                    (Admin Finance) pending, current_step_order=1.
 * - disposisi_result IN (2,3,4)   -> sudah lolos Admin (di model lama): step 1
 *                                    (Admin Finance) approved berdasarkan
 *                                    finance_result (1=approved), step 2 (BM)
 *                                    ikut sm_result (1=approved, else pending).
 * - disposisi_result=5            -> step 1 & 2 approved berdasarkan
 *                                    is_approved (source of truth, BUKAN
 *                                    sm_result -- ada inkonsistensi historis
 *                                    yang sengaja tidak "diperbaiki", sama
 *                                    seperti keputusan Prioritas B1).
 *
 * Kolom lama TIDAK PERNAH ditulis oleh command ini -- murni backfill satu arah
 * ke tabel baru.
 */
class BackfillCustomerVerificationApprovals extends Command
{
    /**
     * @var string
     */
    protected $signature = 'customer-verification:backfill-approvals
        {--commit : Tulis perubahan ke database. Tanpa flag ini, command berjalan dry-run (report only, tidak menulis apapun ke DB).}';

    /**
     * @var string
     */
    protected $description = 'Backfill customer_verifications lama ke document_approvals/document_approval_steps (template customer_verification, 2 step Admin Finance -> BM).';

    private const TEMPLATE_CODE = 'customer_verification';

    public function handle(): int
    {
        $commit = $this->option('commit');

        $template = ApprovalTemplate::where('code', self::TEMPLATE_CODE)->first();

        if (!$template) {
            $this->error('Template approval "' . self::TEMPLATE_CODE . '" belum ada. Jalankan ApprovalTemplateCustomerVerificationSeeder dulu (Prioritas A / CA1).');
            return self::FAILURE;
        }

        $stepIds = $template->steps()->pluck('id_step', 'step_order');

        foreach ([1, 2] as $order) {
            if (!$stepIds->has($order)) {
                $this->error("Template step_order={$order} untuk template \"" . self::TEMPLATE_CODE . '" tidak ditemukan. Cek seeder Prioritas A / CA1.');
                return self::FAILURE;
            }
        }

        $totalRows       = CustomerVerification::count();
        $alreadyMigrated = CustomerVerification::whereHas('documentApprovals')->count();

        // Kandidat: baris yang belum punya document_approvals SAMA SEKALI DAN
        // disposisi_result > 0 (baris disposisi_result=0 sengaja tidak pernah
        // jadi kandidat di model baru -- lihat mapping di docblock kelas ini).
        $candidates = CustomerVerification::whereDoesntHave('documentApprovals')
            ->where('disposisi_result', '>', 0)
            ->get();

        $skippedDraft = CustomerVerification::whereDoesntHave('documentApprovals')
            ->where('disposisi_result', 0)
            ->count();

        $this->info("Total baris customer_verifications : {$totalRows}");
        $this->info("Sudah punya document_approvals (skip, idempotent) : {$alreadyMigrated}");
        $this->info("disposisi_result=0 (belum pernah di-forward Marketing, TIDAK dibuatkan cycle di model baru) : {$skippedDraft}");
        $this->info("Kandidat diproses run ini (disposisi_result > 0) : {$candidates->count()}");
        $this->newLine();

        if ($candidates->isEmpty()) {
            $this->info('Tidak ada baris yang perlu di-backfill. Selesai.');
            return self::SUCCESS;
        }

        $plans = [];
        $anomalies = [];

        foreach ($candidates as $cv) {
            $plan = $this->computePlan($cv);

            if ($plan === null) {
                $anomalies[] = $cv;
                continue;
            }

            $plans[] = $plan;
        }

        $this->renderBreakdown($plans, $anomalies);
        $this->renderDetail($plans);

        if ($anomalies) {
            $this->newLine();
            $this->warn('Baris dengan disposisi_result di luar rentang yang dikenal (1-5) ditemukan dan DILEWATI (tidak ada mapping yang aman untuk itu):');
            foreach ($anomalies as $cv) {
                $this->line("  - id_verification={$cv->id_verification}, disposisi_result=" . var_export($cv->disposisi_result, true));
            }
            Log::warning('BackfillCustomerVerificationApprovals: baris dilewati karena disposisi_result di luar rentang 1-5', [
                'ids' => collect($anomalies)->pluck('id_verification')->all(),
            ]);
        }

        if (!$commit) {
            $this->newLine();
            $this->comment('Dry-run mode (default). Tidak ada perubahan ditulis ke database. Jalankan ulang dengan --commit untuk benar-benar menulis.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('--commit diberikan, menulis ' . count($plans) . ' document_approvals (+ ' . (count($plans) * 2) . ' document_approval_steps)...');

        DB::transaction(function () use ($plans, $stepIds, $template) {
            foreach ($plans as $plan) {
                /** @var CustomerVerification $cv */
                $cv = $plan['cv'];

                $approval = DocumentApproval::create([
                    'id_template'        => $template->id_template,
                    'approvable_type'    => CustomerVerification::class,
                    'approvable_id'      => $cv->id_verification,
                    'status'             => $plan['approval_status'],
                    'current_step_order' => $plan['current_step_order'],
                    'started_at'         => $plan['started_at'],
                    'completed_at'       => $plan['completed_at'],
                ]);

                foreach ($plan['steps'] as $stepOrder => $stepPlan) {
                    DocumentApprovalStep::create([
                        'id_approval'      => $approval->id_approval,
                        'id_template_step' => $stepIds->get($stepOrder),
                        'step_order'       => $stepOrder,
                        'status'           => $stepPlan['status'],
                        'actor_id'         => null,
                        'acted_at'         => $stepPlan['acted_at'],
                        'decision_note'    => $stepPlan['decision_note'],
                    ]);
                }
            }
        });

        $this->info('Selesai. ' . count($plans) . ' document_approvals dibuat.');

        return self::SUCCESS;
    }

    /**
     * Hitung "rencana" backfill untuk satu baris customer_verifications, tanpa
     * menulis apapun. Return null kalau disposisi_result di luar rentang yang
     * dikenal (1-5, karena disposisi_result=0 sudah difilter di query kandidat)
     * -- baris itu dilewati (anomaly), bukan ditebak.
     */
    private function computePlan(CustomerVerification $cv): ?array
    {
        $disp = (int) $cv->disposisi_result;

        if ($disp < 1 || $disp > 5) {
            return null;
        }

        // Tidak ada kolom created_at/created_time-equivalent di
        // customer_verifications ($timestamps=false, sudah dicek via
        // `php artisan db:table customer_verifications`) -- jadi started_at
        // selalu fallback ke now() saat backfill dijalankan, dicatat sebagai
        // approksimasi, bukan waktu submit/forward asli.
        $startedAt = Carbon::now();
        $startedAtNote = 'started_at diisi now() saat backfill -- customer_verifications tidak punya kolom created_at/created_time equivalent.';

        // Step 1 (Admin Finance) -> finance_tgl_proses beneran diisi oleh
        // saveEvaluation(), dipakai sebagai acted_at kalau step ini approved.
        $step1ActedAt = $cv->finance_tgl_proses;
        $step1ActedAtNote = $step1ActedAt
            ? null
            : 'acted_at step 1 (Admin Finance) null -- finance_tgl_proses kosong di baris ini.';

        // Step 2 (BM) -> bmVerify() lama menulis ke 'bm_tgl_proses' yang bukan
        // kolom fillable/nyata (dijatuhkan diam-diam oleh mass-assignment
        // guard), sehingga sm_tgl_proses TIDAK PERNAH terisi (diverifikasi:
        // selalu null di data live). Best-available proxy: om_tgl_proses,
        // yang di kode lama diisi bersamaan (nilainya sama persis dengan
        // tanggal_approved untuk baris yang sudah OM-approve), dipakai sebagai
        // pendekatan waktu keputusan final di bawah model lama.
        $step2ActedAt = $cv->om_tgl_proses;
        $step2ActedAtNote = $step2ActedAt
            ? 'acted_at step 2 (BM) diisi dari om_tgl_proses (proxy -- sm_tgl_proses/bm_tgl_proses lama tidak pernah terisi karena bug mass-assignment di controller lama).'
            : 'acted_at step 2 (BM) null -- om_tgl_proses maupun sm_tgl_proses kosong di baris ini.';

        $steps = [
            1 => ['status' => DocumentApprovalStepStatus::Pending, 'acted_at' => null, 'decision_note' => null],
            2 => ['status' => DocumentApprovalStepStatus::Pending, 'acted_at' => null, 'decision_note' => null],
        ];

        $approvalStatus   = DocumentApprovalStatus::InProgress;
        $currentStepOrder = 1;
        $completedAt      = null;
        $notes            = [$startedAtNote];

        if ($disp === 1) {
            // Sudah lolos Marketing di model lama -- treat sebagai baru saja
            // di-forward: step 1 (Admin Finance) pending, current_step_order=1.
            // Tidak ada perubahan lain.
        } elseif (in_array($disp, [2, 3, 4], true)) {
            // Lolos Admin di model lama (tahap Logistik/BM/OM lama, sudah
            // dihapus dari alur baru) -- step 1 (Admin Finance) approved
            // berdasarkan finance_result, step 2 (BM) ikut sm_result.
            if ((int) $cv->finance_result === 1) {
                $steps[1] = ['status' => DocumentApprovalStepStatus::Approved, 'acted_at' => $step1ActedAt, 'decision_note' => 'Backfill dari disposisi_result=' . $disp . ', finance_result=1 (Admin Finance approve di model lama).'];
                $currentStepOrder = 2;
                $notes[] = $step1ActedAtNote;
            } else {
                $notes[] = 'Step 1 (Admin Finance) dibiarkan pending -- finance_result bukan 1, meskipun disposisi_result=' . $disp . ' menyiratkan "sudah lewat Admin" di model lama (inkonsistensi historis, tidak "diperbaiki").';
            }

            if ((int) $cv->sm_result === 1) {
                $steps[2] = ['status' => DocumentApprovalStepStatus::Approved, 'acted_at' => $step2ActedAt, 'decision_note' => 'Backfill dari disposisi_result=' . $disp . ', sm_result=1 (BM approve di model lama). ' . $step2ActedAtNote];
                $approvalStatus   = DocumentApprovalStatus::Approved;
                $currentStepOrder = null;
                $completedAt      = $cv->tanggal_approved ?: $step2ActedAt ?: Carbon::now();
                $notes[] = $step2ActedAtNote;
            } else {
                // sm_result 0/null -- BM belum benar-benar memutuskan di
                // bawah model baru, jangan menganggap approved.
                $notes[] = 'Step 2 (BM) dibiarkan pending -- sm_result bukan 1, BM belum benar-benar memutuskan di bawah model baru (disposisi_result=' . $disp . ' hanya berarti "sudah lewat Admin" di model lama, bukan keputusan BM).';
            }
        } elseif ($disp === 5) {
            // OM sudah putuskan di bawah model lama. Source of truth: is_approved
            // (BUKAN sm_result -- ada inkonsistensi historis yang sengaja tidak
            // "diperbaiki", lihat catatan plan Prioritas B1/CA2).
            $steps[1] = ['status' => DocumentApprovalStepStatus::Approved, 'acted_at' => $step1ActedAt, 'decision_note' => 'Backfill dari disposisi_result=5 (lolos Admin Finance).'];
            $notes[] = $step1ActedAtNote;

            if ($cv->is_approved) {
                $steps[2] = ['status' => DocumentApprovalStepStatus::Approved, 'acted_at' => $step2ActedAt, 'decision_note' => 'Backfill dari disposisi_result=5, is_approved=1 (source of truth, bukan sm_result). ' . $step2ActedAtNote];
                $approvalStatus   = DocumentApprovalStatus::Approved;
                $currentStepOrder = null;
                $completedAt      = $cv->tanggal_approved ?: $step2ActedAt ?: Carbon::now();
                $notes[] = $step2ActedAtNote;
                if (!$cv->tanggal_approved) {
                    $notes[] = 'completed_at fallback ke ' . ($step2ActedAt ? 'om_tgl_proses' : 'now()') . ' -- tanggal_approved kosong di baris ini.';
                }
            } else {
                $steps[2] = ['status' => DocumentApprovalStepStatus::Rejected, 'acted_at' => $step2ActedAt, 'decision_note' => 'Backfill dari disposisi_result=5, is_approved=0/null (source of truth, bukan sm_result) -- ditandai reject di step 2 (BM), karena keputusan final di model lama jatuh di tahap OM yang sekarang berada setelah BM di model baru. ' . $step2ActedAtNote];
                $approvalStatus   = DocumentApprovalStatus::Rejected;
                $currentStepOrder = null;
                $completedAt      = $cv->tanggal_approved ?: $step2ActedAt ?: Carbon::now();
                $notes[] = $step2ActedAtNote;
            }
        }

        return [
            'cv'                 => $cv,
            'disposisi_result'   => $disp,
            'approval_status'    => $approvalStatus,
            'current_step_order' => $currentStepOrder,
            'started_at'         => $startedAt,
            'completed_at'       => $completedAt,
            'steps'              => $steps,
            'notes'              => array_values(array_unique(array_filter($notes))),
        ];
    }

    private function renderBreakdown(array $plans, array $anomalies): void
    {
        $byDisposisi = collect($plans)->groupBy('disposisi_result')->map->count();

        $rows = [];
        foreach ($byDisposisi as $disp => $count) {
            $rows[] = [$disp, $count];
        }
        if ($anomalies) {
            $rows[] = ['(anomali, di luar 1-5)', count($anomalies)];
        }

        $this->info('Breakdown per disposisi_result:');
        $this->table(['disposisi_result', 'jumlah baris'], $rows);
    }

    private function renderDetail(array $plans): void
    {
        $rows = [];
        foreach ($plans as $plan) {
            $rows[] = [
                $plan['cv']->id_verification,
                $plan['cv']->id_customer,
                $plan['disposisi_result'],
                $plan['approval_status']->value,
                $plan['current_step_order'] ?? '-',
                $plan['steps'][1]['status']->value,
                $plan['steps'][2]['status']->value,
                $plan['completed_at'] ? $plan['completed_at']->toDateTimeString() : '-',
            ];
        }

        $this->newLine();
        $this->info('Detail per baris (rencana backfill):');
        $this->table(
            ['id_verification', 'id_customer', 'disposisi_result', 'approval_status', 'current_step', 'step1 (Admin Finance)', 'step2 (BM)', 'completed_at'],
            $rows
        );

        $this->newLine();
        $this->info('Catatan approksimasi per baris:');
        foreach ($plans as $plan) {
            $this->line("  id_verification={$plan['cv']->id_verification}:");
            foreach ($plan['notes'] as $note) {
                $this->line('    - ' . $note);
            }
        }
    }
}
