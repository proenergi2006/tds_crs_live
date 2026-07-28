<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplateCustomerCreditSeeder extends Seeder
{
    /**
     * Idempotent: aman dijalankan berulang kali (updateOrCreate keyed on
     * code / [id_template, step_order]).
     */
    public function run(): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => 'customer_credit'],
            [
                'name'      => 'Persetujuan Kredit Customer',
                'is_active' => true,
            ]
        );

        $steps = [
            ['step_order' => 1, 'step_name' => 'Admin Finance', 'id_role' => 9],
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
    }
}
