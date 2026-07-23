<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\Seeder;

class ApprovalTemplateCustomerLcrSurveySeeder extends Seeder
{
    /**
     * 1 step Logistik (id_role=6) -- menggantikan flag_disposisi/flag_approval
     * bespoke lama di `customer_lcr`. Role dipilih berdasarkan permission
     * existing yang sudah menggerbangi verifikasi LCR (`logistik.lcr.verify`,
     * lihat PermissionSeeder.php), diverifikasi ulang ke tabel `roles` asli
     * (id_role=6 = Logistik).
     *
     * Idempotent: aman dijalankan berulang kali (updateOrCreate keyed on
     * code / [id_template, step_order]).
     */
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
                'step_name' => 'Logistik',
                'id_role'   => 6,
            ]
        );
    }
}
