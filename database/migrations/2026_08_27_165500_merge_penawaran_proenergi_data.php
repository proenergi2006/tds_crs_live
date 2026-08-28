<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "UPDATE penawarans_proenergi SET tipe_pembayaran = 'TOP ' || top_hari "
            . "WHERE tipe_pembayaran = 'TOP' AND top_hari IS NOT NULL"
        );

        $existingPenawaranIds = DB::table('penawarans')->pluck('id_penawaran')->all();
        $sourceRows = DB::table('penawarans_proenergi')
            ->whereNotIn('id_penawaran', $existingPenawaranIds)
            ->get();

        $newParentIds = $sourceRows->pluck('id_penawaran')->all();

        if ($sourceRows->isNotEmpty()) {
            $penawaranRows = $sourceRows->map(function ($row) {
                $data = (array) $row;
                unset($data['top_hari']);
                $data['brand'] = 'proenergi';
                return $data;
            })->all();

            DB::table('penawarans')->insert($penawaranRows);
        }

        if (!empty($newParentIds)) {
            $itemRows = DB::table('penawaran_items_proenergi')
                ->whereIn('id_penawaran', $newParentIds)
                ->get()
                ->map(function ($row) {
                    $data = (array) $row;
                    unset($data['id_penawaran_item']);
                    return $data;
                })
                ->all();

            if (!empty($itemRows)) {
                DB::table('penawaran_items')->insert($itemRows);
            }

            $ongkosRows = DB::table('penawaran_ongkos_proenergi')
                ->whereIn('penawaran_id', $newParentIds)
                ->get()
                ->map(function ($row) {
                    $data = (array) $row;
                    unset($data['id']);
                    return $data;
                })
                ->all();

            if (!empty($ongkosRows)) {
                DB::table('penawaran_ongkos')->insert($ongkosRows);
            }
        }

        DB::table('document_approvals')
            ->where('approvable_type', 'App\Models\PenawaranProenergi')
            ->update(['approvable_type' => 'App\Models\Penawaran']);
    }

    public function down(): void
    {
        $revertedIds = DB::table('penawarans')->where('brand', 'proenergi')->pluck('id_penawaran');

        DB::table('document_approvals')
            ->where('approvable_type', 'App\Models\Penawaran')
            ->whereIn('approvable_id', $revertedIds)
            ->update(['approvable_type' => 'App\Models\PenawaranProenergi']);

        DB::table('penawarans')->where('brand', 'proenergi')->delete();
    }
};
