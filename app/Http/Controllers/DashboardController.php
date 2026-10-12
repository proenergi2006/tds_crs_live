<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildMarketingRecentActivityAction;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Enums\VendorPoApprovalState;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\VendorPo;
use App\Support\ProductPrice\PricePeriodCompletenessQuery;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function marketingSummary(Request $request)
    {
        $userId = $request->user()->id;

        $customerBase = Customer::where('id_user', $userId);
        $customerTotal = (clone $customerBase)->count();

        $customerVerified = (clone $customerBase)
            ->whereHas('latestVerification', fn($vq) => $vq->whereHas('latestDocumentApproval', fn($aq) => $aq->where('status', DocumentApprovalStatus::Approved)))
            ->count();
        $customerUnverified = $customerTotal - $customerVerified;

        $quotationBase = Quotation::where('user_id', $userId);
        $quotationTotal = (clone $quotationBase)->count();
        $quotationApproved = (clone $quotationBase)->where('status', QuotationStatus::Approved)->count();
        $quotationUnapproved = $quotationTotal - $quotationApproved;

        return response()->json([
            'customers' => [
                'total'      => $customerTotal,
                'verified'   => $customerVerified,
                'unverified' => $customerUnverified,
            ],
            'penawarans' => [
                'total'      => $quotationTotal,
                'approved'   => $quotationApproved,
                'unapproved' => $quotationUnapproved,
            ],
            'recent_activities' => app(BuildMarketingRecentActivityAction::class)->execute($userId, 10),
        ]);
    }

    public function agentSummary(Request $request)
    {
        $query = Quotation::where('brand', 'proenergi');

        $total = (clone $query)->count();

        $draft = (clone $query)
            ->where('status', QuotationStatus::Draft)
            ->count();

        $pending = (clone $query)
            ->where('status', QuotationStatus::WaitingVerification)
            ->count();

        $approved = (clone $query)
            ->where('status', QuotationStatus::Approved)
            ->count();

        return response()->json([
            'total_penawaran'   => $total,
            'draft_penawaran'   => $draft,
            'pending_penawaran' => $pending,
            'approved_penawaran'=> $approved,
        ]);
    }

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

    private function vendorPoMonthlyTrend(int $year): array
    {
        $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $end   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

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

    private function pendingCeoPricePeriod(): array
    {
        $pending = app(PricePeriodCompletenessQuery::class)->groupedCurrentAndUpcoming()
            ->filter(fn ($row) => (int) $row->jumlah_belum_lengkap > 0)
            ->values();

        $items = $pending->take(10)->map(fn ($row) => [
            'start_date'           => $row->start_date,
            'end_date'             => $row->end_date,
            'jumlah_data'          => (int) $row->jumlah_data,
            'jumlah_belum_lengkap' => (int) $row->jumlah_belum_lengkap,
        ]);

        return ['total' => $pending->count(), 'items' => $items->values()->all()];
    }

    public function omSummary(Request $request)
    {
        if ($request->user()->cant('dashboard.view-om')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'penawaran_approval_queue' => $this->quotationApprovalQueue(),
            'bm_queue_context'         => $this->bmQueueContext(),
            'penawaran_funnel'         => $this->quotationFunnel(),
        ]);
    }

    private function quotationApprovalQueue(): array
    {
        $roles = QuotationVerifierRole::operationManagerRoles();
        $total = Quotation::waitingAtRoles($roles)->count();

        $items = Quotation::waitingAtRoles($roles)
            ->with(['customer', 'latestDocumentApproval.steps'])
            ->get()
            ->map(fn ($p) => [
                'quotation_id'    => $p->id,
                'nomor_penawaran' => $p->nomor_penawaran,
                'customer_name'   => optional($p->customer)->company_name,
                'waiting_since'   => $p->waitingSince(),
                'aging_days'      => $p->waitingSince() ? now()->diffInDays($p->waitingSince()) : null,
            ])
            ->sortBy('waiting_since')
            ->take(10)
            ->values();

        return ['total' => $total, 'items' => $items->all()];
    }

    private function bmQueueContext(): array
    {
        return ['total' => Quotation::waitingAtRoles(QuotationVerifierRole::branchManagerRoles())->count()];
    }

    private function quotationFunnel(): array
    {
        $funnel = ['draft' => 0, 'waiting_bm' => 0, 'waiting_om' => 0, 'waiting_ceo' => 0, 'approved' => 0, 'rejected_bm' => 0, 'rejected_om' => 0];
        $bmRoles = QuotationVerifierRole::branchManagerRoles();
        $omRoles = QuotationVerifierRole::operationManagerRoles();

        foreach (Quotation::with('latestDocumentApproval.steps')->get(['id', 'status']) as $quotation) {
            $roleId = (int) $quotation->activeVerificationStep()?->id_role;

            $key = match ($quotation->status) {
                QuotationStatus::Draft    => 'draft',
                QuotationStatus::Approved => 'approved',
                QuotationStatus::Rejected => in_array($this->rejectedStepRole($quotation), $bmRoles, true) ? 'rejected_bm' : 'rejected_om',
                QuotationStatus::WaitingVerification => match (true) {
                    in_array($roleId, $bmRoles, true) => 'waiting_bm',
                    in_array($roleId, $omRoles, true) => 'waiting_om',
                    default                           => 'waiting_ceo',
                },
                default => null,
            };

            if ($key) {
                $funnel[$key]++;
            }
        }

        return $funnel;
    }

    private function rejectedStepRole(Quotation $quotation): int
    {
        return (int) $quotation->latestDocumentApproval?->steps
            ->firstWhere('status', DocumentApprovalStepStatus::Rejected)?->id_role;
    }
}
