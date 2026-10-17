<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const APPROVABLE_TYPE = 'App\Models\SalesConfirmation';

    private const PENDING_BM_DISPOSISI = 2;

    private const OM_ROLE_BY_BRAND = [
        'tds' => 10,
        'proenergi' => 16,
    ];

    public function up(): void
    {
        $rows = $this->activePendingBmCycles()->get(['da.id_approval', 'q.brand']);
        $now = now();

        foreach ($rows as $row) {
            $roleId = self::OM_ROLE_BY_BRAND[$row->brand] ?? null;

            if ($roleId === null) {
                throw new RuntimeException('Unknown quotation brand "'.$row->brand.'" on approval '.$row->id_approval.'.');
            }

            $exists = DB::table('document_approval_steps')
                ->where('id_approval', $row->id_approval)
                ->where('step_order', 2)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('document_approval_steps')->insert([
                'id_approval' => $row->id_approval,
                'id_template_step' => null,
                'id_role' => $roleId,
                'step_order' => 2,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $approvalIds = $this->activePendingBmCycles()->pluck('da.id_approval');

        DB::table('document_approval_steps')
            ->whereIn('id_approval', $approvalIds)
            ->where('step_order', 2)
            ->where('status', 'pending')
            ->whereIn('id_role', array_values(self::OM_ROLE_BY_BRAND))
            ->delete();
    }

    private function activePendingBmCycles(): \Illuminate\Database\Query\Builder
    {
        return DB::table('document_approvals as da')
            ->join('sales_confirmation as sc', 'sc.id', '=', 'da.approvable_id')
            ->join('po_customers as poc', 'poc.id_poc', '=', 'sc.po_customer_id')
            ->join('quotations as q', 'q.id', '=', 'poc.quotation_id')
            ->where('da.approvable_type', self::APPROVABLE_TYPE)
            ->where('da.status', 'in_progress')
            ->where('sc.disposisi', self::PENDING_BM_DISPOSISI);
    }
};
