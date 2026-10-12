<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplateCustomerLcrSurveySeeder extends Seeder
{
    public function run(): void
    {
        $template = ApprovalTemplate::updateOrCreate(
            ['code' => 'customer_lcr_survey'],
            [
                'name'      => 'Verifikasi Survei LCR',
                'is_active' => true,
            ]
        );

        ApprovalTemplateStep::updateOrCreate(
            [
                'id_template' => $template->id_template,
                'step_order'  => 1,
            ],
            [
                'step_name' => 'Logistic Branch',
                'id_role'   => 6,
            ]
        );
    }
}
