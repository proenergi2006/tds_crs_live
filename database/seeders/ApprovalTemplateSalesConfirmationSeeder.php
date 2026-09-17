<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplateSalesConfirmationSeeder extends Seeder
{
    public function run(): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => 'sales_confirmation'],
            [
                'name' => 'Approval Sales Confirmation',
                'is_active' => true,
            ]
        );

        ApprovalTemplateStep::updateOrCreate(
            [
                'id_template' => $template->id_template,
                'step_order' => 1,
            ],
            [
                'step_name' => 'Branch Manager',
                'id_role' => 8,
            ]
        );
    }
}
