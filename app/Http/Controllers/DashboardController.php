<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function agentSummary(Request $request)
    {
        $user = $request->user();

        /**
         * ⚠️ SESUAIKAN FILTER INI
         * Kalau agent hanya boleh lihat data sendiri:
         * ->where('created_by', $user->id)
         * 
         * Kalau berdasarkan cabang:
         * ->where('id_cabang', $user->id_cabang)
         */

        $query = DB::table('penawarans_proenergi');

        // OPTIONAL FILTER (aktifkan kalau perlu)
        // $query->where('created_by', $user->id);

        $total = (clone $query)->count();

        $draft = (clone $query)
            ->where('disposisi_penawaran', 1)
            ->count();

        $pending = (clone $query)
            ->whereIn('disposisi_penawaran', [2, 3]) // BM & OM
            ->count();

        $approved = (clone $query)
            ->where('disposisi_penawaran', 4)
            ->count();

        return response()->json([
            'total_penawaran'   => $total,
            'draft_penawaran'   => $draft,
            'pending_penawaran' => $pending,
            'approved_penawaran'=> $approved,
        ]);
    }
}