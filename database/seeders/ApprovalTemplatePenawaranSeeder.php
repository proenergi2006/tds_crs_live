<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplatePenawaranSeeder extends Seeder
{
    /**
     * Seed template approval Penawaran (TDS & Proenergi) untuk mesin
     * DocumentApproval. Step OM TDS disederhanakan jadi 1 role approver
     * (Operation Manager, id_role=10) -- CEO/CFO bukan lagi approver
     * langsung di step ini (keputusan Engineer, migrasi Tahap 1).
     *
     * Idempotent: aman dijalankan berulang (updateOrCreate keyed on
     * code / [id_template, step_order]).
     */
    public function run(): void
    {
        $this->seedTemplate('penawaran_tds', 'Approval Penawaran TDS', [
            ['step_order' => 1, 'step_name' => 'Branch Manager', 'id_role' => 8],
            ['step_order' => 2, 'step_name' => 'Operation Manager', 'id_role' => 10],
        ]);

        $this->seedTemplate('penawaran_proenergi', 'Approval Penawaran Proenergi', [
            ['step_order' => 1, 'step_name' => 'Branch Manager Proenergi', 'id_role' => 15],
            ['step_order' => 2, 'step_name' => 'OM Proenergi', 'id_role' => 16],
        ]);
    }

    private function seedTemplate(string $code, string $name, array $steps): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'is_active' => true,
            ]
        );

        foreach ($steps as $step) {
            ApprovalTemplateStep::updateOrCreate(
                [
                    'id_template' => $template->id_template,
                    'step_order' => $step['step_order'],
                ],
                [
                    'step_name' => $step['step_name'],
                    'id_role' => $step['id_role'],
                ]
            );
        }
    }
}
