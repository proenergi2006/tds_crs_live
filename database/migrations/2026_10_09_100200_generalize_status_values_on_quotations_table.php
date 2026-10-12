<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_TO_NEW = [
        'draft' => 'draft',
        'waiting_branch_manager' => 'waiting_verification',
        'approved_bm' => 'waiting_verification',
        'approved_om' => 'approved',
        'rejected_bm' => 'rejected',
        'rejected_om' => 'rejected',
    ];

    private const DISPOSISI_TO_OLD = [
        2 => 'waiting_branch_manager',
        3 => 'approved_bm',
        4 => 'approved_om',
        5 => 'rejected_bm',
        6 => 'rejected_om',
    ];

    private const FALLBACK_NEW_TO_OLD = [
        'draft' => 'draft',
        'waiting_verification' => 'waiting_branch_manager',
        'approved' => 'approved_om',
        'rejected' => 'rejected_bm',
    ];

    private const NEW_VALUES = ['draft', 'waiting_verification', 'approved', 'rejected'];

    private const LEGACY_VALUES = ['draft', 'waiting_branch_manager', 'approved_bm', 'rejected_bm', 'approved_om', 'rejected_om'];

    public function up(): void
    {
        $this->assertStatusesWithin(array_keys(self::OLD_TO_NEW));

        DB::statement('ALTER TABLE quotations DROP CONSTRAINT quotations_status_check');

        foreach (self::OLD_TO_NEW as $old => $new) {
            if ($old !== $new) {
                DB::table('quotations')->where('status', $old)->update(['status' => $new]);
            }
        }

        $this->addStatusCheck(self::NEW_VALUES);
    }

    public function down(): void
    {
        $this->assertStatusesWithin(self::NEW_VALUES);

        DB::statement('ALTER TABLE quotations DROP CONSTRAINT quotations_status_check');

        foreach (self::DISPOSISI_TO_OLD as $disposisi => $old) {
            DB::table('quotations')
                ->where('disposisi_penawaran', $disposisi)
                ->where('status', self::OLD_TO_NEW[$old])
                ->update(['status' => $old]);
        }

        $legacy = self::LEGACY_VALUES;

        foreach (self::FALLBACK_NEW_TO_OLD as $new => $old) {
            DB::table('quotations')
                ->where('status', $new)
                ->whereNotIn('status', $legacy)
                ->update(['status' => $old]);
        }

        $this->addStatusCheck($legacy);
    }

    private function assertStatusesWithin(array $allowed): void
    {
        $unexpected = DB::table('quotations')
            ->whereNotIn('status', $allowed)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        if ($unexpected->isNotEmpty()) {
            $detail = $unexpected->map(fn ($row) => "{$row->status} ({$row->total})")->implode(', ');
            throw new RuntimeException("quotations.status holds values outside the expected set: {$detail}.");
        }
    }

    private function addStatusCheck(array $values): void
    {
        $list = "'" . implode("','", $values) . "'";
        DB::statement("ALTER TABLE quotations ADD CONSTRAINT quotations_status_check CHECK (status IN ({$list}))");
    }
};
