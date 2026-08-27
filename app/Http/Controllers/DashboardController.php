<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildMarketingRecentActivityAction;
use App\Enums\DocumentApprovalStatus;
use App\Enums\VendorPoApprovalState;
use App\Models\Customer;
use App\Models\Penawaran;
use App\Models\VendorPo;
use App\Support\Dashboard\StalePenawaranPriceQuery;
use App\Support\ProdukHarga\PricePeriodCompletenessQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function marketingSummary(Request $request)
    {
        $userId = $request->user()->id;

        $customerBase = Customer::where('id_user', $userId);
        $customerTotal = (clone $customerBase)->count();

        // kondisi "verified" harus sama persis kayak CustomerController::applyStatusFilter() (case 'verified') -- duplikasi disengaja, beda controller.
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
        $query = DB::table('penawarans_proenergi');

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

    // role gate eksplisit -- data di sini sensitif, beda dari marketingSummary/agentSummary yang gak ada cek role
    public function ceoSummary(Request $request)
    {
        if ($request->user()->cant('dashboard.view-ceo')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $currentYear = (int) now()->format('Y');

        return response()->json([
            'vendor_po_approval_queue' => $this->vendorPoApprovalQueue(),
            'vendor_po_value_summary'  => [
                'by_status' => $this->vendorPoStatusSummary($currentYear),
                'by_vendor' => $this->vendorPoVendorSummary($currentYear),
            ],
            'vendor_po_monthly_trend'  => $this->vendorPoMonthlyTrend($currentYear),
            'pending_ceo_price_period' => $this->pendingCeoPricePeriod(),
        ]);
    }

    // endpoint ringan sendiri biar ganti dropdown tahun gak perlu re-fetch seluruh dashboard
    public function ceoPoMonthlyTrend(Request $request)
    {
        if ($request->user()->cant('dashboard.view-ceo')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $year = (int) ($request->query('year') ?: now()->format('Y'));

        return response()->json([
            'year'   => $year,
            'points' => $this->vendorPoMonthlyTrend($year),
        ]);
    }

    // dropdown tahun-nya sengaja independen dari dropdown chart tren PO, dua card ini gak saling terikat
    public function ceoVendorValueSummary(Request $request)
    {
        if ($request->user()->cant('dashboard.view-ceo')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $year = (int) ($request->query('year') ?: now()->format('Y'));

        return response()->json([
            'year'      => $year,
            'by_vendor' => $this->vendorPoVendorSummary($year),
        ]);
    }

    private function vendorPoApprovalQueue(): array
    {
        $query = VendorPo::where('disposisi_po', VendorPoApprovalState::WaitingCeo->value)->with('vendor');

        $items = (clone $query)
            ->orderBy('cfo_tgl', 'asc')
            ->limit(10)
            ->get()
            ->map(fn ($po) => [
                'id_po'         => $po->id_po,
                'nomor_po'      => $po->nomor_po,
                'vendor_name'   => optional($po->vendor)->nama_vendor,
                'total_order'   => $po->total_order,
                'waiting_since' => $po->cfo_tgl,
                'aging_days'    => now()->diffInDays($po->cfo_tgl),
            ]);

        return ['total' => (clone $query)->count(), 'items' => $items->all()];
    }

    // dibatasi ke $year biar KPI "Valuasi Stok Procurement" gak terus membesar all-time -- fixed ke tahun berjalan, gak ada dropdown
    private function vendorPoStatusSummary(int $year): array
    {
        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        $byStatus = VendorPo::whereBetween('tanggal_inven', [$start, $end])
            ->get()
            ->groupBy(fn ($po) => VendorPoApprovalState::resolve($po)->name)
            ->map(fn ($group) => [
                'count'       => $group->count(),
                'total_order' => $group->sum('total_order'),
            ]);

        return $byStatus->all();
    }

    // sengaja cuma status Approved (bukan outstanding), biar konsisten sama vendorPoStatusSummary di atas
    private function vendorPoVendorSummary(int $year): array
    {
        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        $byVendor = VendorPo::where('disposisi_po', VendorPoApprovalState::Approved->value)
            ->whereBetween('tanggal_inven', [$start, $end])
            ->with('vendor')
            ->get()
            ->groupBy('id_vendor')
            ->map(fn ($group) => [
                'vendor_name' => optional($group->first()->vendor)->nama_vendor,
                'count'       => $group->count(),
                'total_order' => $group->sum('total_order'),
            ]);

        return $byVendor->all();
    }

    // pake tanggal_inven bukan created_time -- created_time NULL di semua row existing
    private function vendorPoMonthlyTrend(int $year): array
    {
        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        // TO_CHAR, bukan DATE_FORMAT -- ini Postgres bukan MySQL
        $counts = VendorPo::whereBetween('tanggal_inven', [$start, $end])
            ->selectRaw("TO_CHAR(tanggal_inven, 'MM') as m, COUNT(*) as total")
            ->groupBy('m')
            ->pluck('total', 'm');

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%02d', $m);
            $result[] = [
                'month' => sprintf('%04d-%s', $year, $key),
                'label' => $monthLabels[$m - 1],
                'total' => (int) ($counts->get($key) ?? 0),
            ];
        }

        return $result;
    }

    // dihitung per PERIODE bukan per baris produk, pake query yang sama kayak ProdukHargaController::periode() biar definisinya gak beda-beda
    private function pendingCeoPricePeriod(): array
    {
        $pending = app(PricePeriodCompletenessQuery::class)->grouped()
            ->filter(fn ($row) => (int) $row->jumlah_belum_lengkap > 0)
            ->values();

        $items = $pending->take(10)->map(fn ($row) => [
            'periode_awal'         => $row->periode_awal,
            'periode_akhir'        => $row->periode_akhir,
            'jumlah_data'          => (int) $row->jumlah_data,
            'jumlah_belum_lengkap' => (int) $row->jumlah_belum_lengkap,
        ]);

        return ['total' => $pending->count(), 'items' => $items->values()->all()];
    }

    // role gate eksplisit, sama kayak ceoSummary()
    public function omSummary(Request $request)
    {
        if ($request->user()->cant('dashboard.view-om')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'penawaran_approval_queue' => $this->penawaranApprovalQueue(),
            'bm_queue_context'         => $this->bmQueueContext(),
            'penawaran_funnel'         => $this->penawaranFunnel(),
            'stale_price_in_penawaran' => app(StalePenawaranPriceQuery::class)->summarize(),
        ]);
    }

    private function penawaranApprovalQueue(): array
    {
        $query = Penawaran::where('disposisi_penawaran', 3)->with('customer');

        $items = (clone $query)
            ->orderBy('bm_tanggal', 'asc')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id_penawaran'    => $p->id_penawaran,
                'nomor_penawaran' => $p->nomor_penawaran,
                'customer_name'   => optional($p->customer)->company_name,
                'waiting_since'   => $p->bm_tanggal,
                'aging_days'      => now()->diffInDays($p->bm_tanggal),
            ]);

        return ['total' => (clone $query)->count(), 'items' => $items->all()];
    }

    private function bmQueueContext(): array
    {
        return ['total' => Penawaran::where('disposisi_penawaran', 2)->count()];
    }

    private function penawaranFunnel(): array
    {
        $counts = Penawaran::selectRaw('disposisi_penawaran, count(*) as total')
            ->groupBy('disposisi_penawaran')
            ->pluck('total', 'disposisi_penawaran');

        // disposisi=0 itu default kolom lama (belum keisi eksplisit), tetep digabung ke draft kayak nilai 1
        return [
            'draft'       => (int) ($counts->get(0, 0) + $counts->get(1, 0)),
            'waiting_bm'  => (int) $counts->get(2, 0),
            'waiting_om'  => (int) $counts->get(3, 0),
            'approved'    => (int) $counts->get(4, 0),
            'rejected_bm' => (int) $counts->get(5, 0),
            'rejected_om' => (int) $counts->get(6, 0),
        ];
    }
}