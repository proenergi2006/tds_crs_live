<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplateCustomerVerificationSeeder extends Seeder
{
    /**
     * Seed template approval `customer_verification` (2 step final, pivot
     * 2026-07-10): Admin Finance (id_role=9) -> BM (id_role=8), final.
     *
     * Marketing (id_role=12) BUKAN LAGI step approval formal di sistem
     * polymorphic ini -- Marketing adalah gate verifikasi/edit yang MEMICU
     * pembuatan siklus (lihat `saveReview()` di CustomerVerificationController).
     * Desain awal (Prioritas A) memakai 3 step (Marketing/Admin/BM); pivot
     * mengubahnya jadi 2 step di sini.
     *
     * Idempotent: aman dijalankan berulang kali (updateOrCreate keyed on
     * code / [id_template, step_order]), tidak akan menghasilkan duplikat.
     * Karena seeder versi lama sempat membuat step_order=1 (Marketing) dan
     * step_order=3 (BM), re-run seeder ini meng-UPDATE step_order=1 in place
     * (Marketing -> Admin Finance) dan step_order=2 in place (Admin Finance ->
     * BM), lalu secara eksplisit MENGHAPUS sisa row step_order > 2 (bekas BM
     * lama di step_order=3) supaya tidak ada row stale tersisa.
     */
    public function run(): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => 'customer_verification'],
            [
                'name'      => 'Verifikasi Customer',
                'is_active' => true,
            ]
        );

        $steps = [
            ['step_order' => 1, 'step_name' => 'Admin Finance', 'id_role' => 9],
            ['step_order' => 2, 'step_name' => 'BM', 'id_role' => 8],
        ];

        foreach ($steps as $step) {
            ApprovalTemplateStep::updateOrCreate(
                [
                    'id_template' => $template->id_template,
                    'step_order'  => $step['step_order'],
                ],
                [
                    'step_name' => $step['step_name'],
                    'id_role'   => $step['id_role'],
                ]
            );
        }

        // Bersihkan sisa step_order > 2 dari struktur 3-step lama (mis.
        // step_order=3/BM sebelum pivot). Aman dihapus karena document_approval_steps
        // yang mereferensikan step lama ini sudah di-wipe sebagai bagian dari
        // koreksi backfill (lihat BackfillCustomerVerificationApprovals) --
        // kalau belum di-wipe, delete ini akan gagal karena FK restrict.
        ApprovalTemplateStep::where('id_template', $template->id_template)
            ->where('step_order', '>', 2)
            ->delete();
    }
}
