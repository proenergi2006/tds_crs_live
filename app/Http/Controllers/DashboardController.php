<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildMarketingRecentActivityAction;
use App\Enums\DocumentApprovalStatus;
use App\Models\Customer;
use App\Models\Penawaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function marketingSummary(Request $request)
    {
        $userId = $request->user()->id;

        $customerBase = Customer::where('id_user', $userId);
        $customerTotal = (clone $customerBase)->count();

        // Kondisi "verified" ini HARUS byte-identik dengan
        // CustomerController::applyStatusFilter() (case 'verified') -- kalau
        // logic di sana berubah, sinkronkan juga di sini. Duplikasi ini
        // disengaja (beda controller), bukan lupa refactor.
        $customerVerified = (clone $customerBase)
            ->whereHas('latestVerification', fn($vq) => $vq->whereHas('latestDocumentApproval', fn($aq) => $aq->where('status', DocumentApprovalStatus::Approved)))
            ->count();
        $customerUnverified = $customerTotal - $customerVerified;

        $penawaranBase = Penawaran::where('user_id', $userId);
        $penawaranTotal = (clone $penawaranBase)->count();
        $penawaranApproved = (clone $penawaranBase)->where('status', 'approved_om')->count();
        $penawaranUnapproved = $penawaranTotal - $penawaranApproved;

        return response()->json([
            'customers' => [
                'total'      => $customerTotal,
                'verified'   => $customerVerified,
                'unverified' => $customerUnverified,
            ],
            'penawarans' => [
                'total'      => $penawaranTotal,
                'approved'   => $penawaranApproved,
                'unapproved' => $penawaranUnapproved,
            ],
            'recent_activities' => app(BuildMarketingRecentActivityAction::class)->execute($userId, 10),
        ]);
    }

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