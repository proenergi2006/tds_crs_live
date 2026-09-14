<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplatePoCustomerUnblockSeeder extends Seeder
{
    public function run(): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => 'po_customer_unblock'],
            [
                'name' => 'Unblock Kredit PO Customer',
                'is_active' => true,
            ]
        );

        ApprovalTemplateStep::updateOrCreate(
            [
                'id_template' => $template->id_template,
                'step_order' => 1,
            ],
            [
                'step_name' => 'Admin Finance',
                'id_role' => 9,
            ]
        );

        ApprovalTemplateStep::updateOrCreate(
            [
                'id_template' => $template->id_template,
                'step_order' => 2,
            ],
            [
                'step_name' => 'Branch Manager',
                'id_role' => 8,
            ]
        );
    }
}
