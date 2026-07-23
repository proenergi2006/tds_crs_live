<?php

namespace App\Http\Controllers;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Http\Requests\Customer\StoreCustomerLcrRequest;
use App\Http\Requests\Customer\UpdateCustomerLcrRequest;
use App\Models\Customer;
use App\Models\CustomerLcr;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerLcrController extends Controller
{
    private const APPROVAL_TEMPLATE_CODE = 'customer_lcr_survey';
    private const ROLE_LOGISTIK = 6;

    public function __construct(private readonly DocumentApprovalService $approvalService)
    {
    }

    /** List site LCR milik 1 customer. */
    public function index(Request $request, Customer $customer)
    {
        if (!$this->canViewCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $sites = $customer->lcr()
            ->with('latestDocumentApproval')
            ->orderByDesc('id_lcr')
            ->get();

        return response()->json($sites->map(fn (CustomerLcr $site) => $this->formatSite($site))->values());
    }

    public function show(Request $request, Customer $customer, CustomerLcr $lcrSite)
    {
        if (!$this->canViewCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($lcrSite->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Site LCR tidak ditemukan untuk customer ini.'], 404);
        }

        $lcrSite->load('latestDocumentApproval');

        return response()->json($this->formatSite($lcrSite));
    }

    public function store(StoreCustomerLcrRequest $request, Customer $customer)
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        $user = $request->user();
        $ip   = $request->ip();

        $site = DB::transaction(function () use ($data, $customer, $user, $ip) {
            $site = CustomerLcr::create([
                ...$data,
                'id_customer'     => $customer->id_customer,
                'created_time'    => now(),
                'created_ip'      => $ip,
                'created_by'      => $user->name ?? 'system',
                'lastupdate_time' => now(),
                'lastupdate_ip'   => $ip,
                'lastupdate_by'   => $user->name ?? 'system',
            ]);

            $this->approvalService->startCycle($site, self::APPROVAL_TEMPLATE_CODE);

            return $site;
        });

        $site->load('latestDocumentApproval');

        return response()->json($this->formatSite($site), 201);
    }

    public function update(UpdateCustomerLcrRequest $request, Customer $customer, CustomerLcr $lcrSite)
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($lcrSite->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Site LCR tidak ditemukan untuk customer ini.'], 404);
        }

        $user = $request->user();

        $lcrSite->update([
            ...$request->validated(),
            'lastupdate_time' => now(),
            'lastupdate_ip'   => $request->ip(),
            'lastupdate_by'   => $user->name ?? 'system',
        ]);

        return response()->json($this->formatSite($lcrSite->fresh('latestDocumentApproval')));
    }

    public function destroy(Request $request, Customer $customer, CustomerLcr $lcrSite)
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($lcrSite->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Site LCR tidak ditemukan untuk customer ini.'], 404);
        }

        $lcrSite->delete();

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request)
    {
        $user = $request->user();

        if (!$user->can('customer.manage') && !$user->can(self::verifyPermission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('file')->store('lcr', 'public');

        return response()->json([
            'path' => $path,
            'url'  => asset('storage/'.$path),
            'name' => $request->file('file')->getClientOriginalName(),
            'size' => $request->file('file')->getSize(),
        ]);
    }

    /**
     * Antrean review Logistik lintas-customer (semua site LCR yang punya
     * siklus approval aktif/riwayat), menggantikan `indexLogistik` lama yang
     * memfilter `flag_disposisi` mentah.
     */
    public function reviewIndex(Request $request)
    {
        if (!$request->user()->can(self::verifyPermission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) $request->query('per_page', 10);
        $q       = trim((string) $request->query('q', ''));
        $status  = $request->query('status', 'pending');

        $query = CustomerLcr::query()
            ->with(['customer:id_customer,nama_perusahaan', 'latestDocumentApproval']);

        if ($request->filled('id_customer')) {
            $query->where('id_customer', (int) $request->query('id_customer'));
        }

        if ($status !== 'all') {
            $mapped = match ($status) {
                'pending'   => DocumentApprovalStatus::InProgress,
                'disetujui' => DocumentApprovalStatus::Approved,
                'ditolak'   => DocumentApprovalStatus::Rejected,
                default     => null,
            };

            if ($mapped) {
                $query->whereHas('latestDocumentApproval', function ($approvalQuery) use ($mapped) {
                    $approvalQuery->where('status', $mapped);
                });
            }
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('site_name', 'like', "%{$q}%")
                    ->orWhere('survey_address', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('nama_perusahaan', 'like', "%{$q}%");
                    });
            });
        }

        $paginated = $query->orderByDesc('id_lcr')->paginate($perPage);
        $paginated->getCollection()->transform(fn (CustomerLcr $site) => $this->formatSite($site));

        return response()->json($paginated);
    }

    public function reviewShow(Request $request, CustomerLcr $lcrSite)
    {
        if (!$request->user()->can(self::verifyPermission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lcrSite->load(['customer:id_customer,nama_perusahaan', 'latestDocumentApproval.steps']);

        return response()->json($this->formatSite($lcrSite));
    }

    public function approvalTimeline(Request $request, CustomerLcr $lcrSite)
    {
        $user = $request->user();

        $allowed = $user->can(self::verifyPermission())
            || $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $lcrSite->customer?->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cycles = $lcrSite->documentApprovals()
            ->with([
                'steps' => fn ($q) => $q->orderBy('step_order'),
                'steps.templateStep:id_step,step_name,step_order,id_role',
                'steps.actor:id,name',
            ])
            ->orderByDesc('id_approval')
            ->get();

        return response()->json([
            'data' => $cycles->map(fn (DocumentApproval $cycle) => [
                'id_approval'        => $cycle->id_approval,
                'status'             => $cycle->status,
                'current_step_order' => $cycle->current_step_order,
                'started_at'         => $cycle->started_at,
                'completed_at'       => $cycle->completed_at,
                'steps'              => $cycle->steps->map(fn (DocumentApprovalStep $step) => [
                    'step_order'    => $step->step_order,
                    'step_name'     => $step->templateStep->step_name ?? null,
                    'status'        => $step->status,
                    'actor_id'      => $step->actor_id,
                    'actor_name'    => $step->actor->name ?? null,
                    'acted_at'      => $step->acted_at,
                    'decision_note' => $step->decision_note,
                ]),
            ]),
        ]);
    }

    /** Approve/reject siklus aktif (menggantikan `setFlag` lama). */
    public function decide(Request $request, CustomerLcr $lcrSite)
    {
        if (!$request->user()->can(self::verifyPermission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note'     => ['nullable', 'string'],
        ]);

        $template  = $this->approvalService->activeTemplate(self::APPROVAL_TEMPLATE_CODE);
        $stepOrder = $template ? $this->approvalService->resolveStepOrderForRole($template, self::ROLE_LOGISTIK) : null;

        if ($stepOrder === null) {
            return response()->json(['message' => 'Approval template customer_lcr_survey belum ter-setup dengan benar.'], 422);
        }

        $status = $data['decision'] === 'approve'
            ? DocumentApprovalStepStatus::Approved
            : DocumentApprovalStepStatus::Rejected;

        $cycle = DB::transaction(fn () => $this->approvalService->decideStep(
            $lcrSite,
            $stepOrder,
            $status,
            $request->user()->id,
            $data['note'] ?? null
        ));

        if (!$cycle) {
            return response()->json(['message' => 'Tidak ada siklus approval aktif untuk site LCR ini.'], 409);
        }

        return response()->json($this->formatSite($lcrSite->fresh('latestDocumentApproval')));
    }

    /** Buka siklus approval baru (menggantikan `resetFlag` lama). */
    public function resetDecision(Request $request, CustomerLcr $lcrSite)
    {
        if (!$request->user()->can(self::verifyPermission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::transaction(fn () => $this->approvalService->startCycle($lcrSite, self::APPROVAL_TEMPLATE_CODE));

        return response()->json($this->formatSite($lcrSite->fresh('latestDocumentApproval')));
    }

    private static function verifyPermission(): string
    {
        return 'logistik.lcr.verify';
    }

    private function canViewCustomer(Request $request, Customer $customer): bool
    {
        $user = $request->user();

        return $user->can('customer.viewAny')
            || $user->can(self::verifyPermission())
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);
    }

    private function canManageCustomer(Request $request, Customer $customer): bool
    {
        $user = $request->user();

        return $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));
    }

    private function formatSite(CustomerLcr $site): array
    {
        $cycle = $site->latestDocumentApproval;

        return [
            'id_lcr'      => $site->id_lcr,
            'id_customer' => $site->id_customer,
            'customer'    => $site->relationLoaded('customer') && $site->customer ? [
                'id_customer'     => $site->customer->id_customer,
                'nama_perusahaan' => $site->customer->nama_perusahaan,
            ] : null,

            /* Grup 1 */
            'site_name'                => $site->site_name,
            'survey_address'           => $site->survey_address,
            'prov_survey'              => $site->prov_survey,
            'kab_survey'               => $site->kab_survey,
            'survey_date'              => optional($site->survey_date)->toDateString(),
            'surveyor_names'           => $site->surveyor_names,
            'site_business_type'       => $site->site_business_type,
            'site_business_type_other' => $site->site_business_type_other,
            'site_environment'         => $site->site_environment,
            'site_environment_other'   => $site->site_environment_other,
            'site_environment_notes'   => $site->site_environment_notes,
            'competitors'              => $site->competitors,
            'operating_hours'          => $site->operating_hours,
            'product_volume'           => $site->product_volume,
            'survey_notes'             => $site->survey_notes,
            'picustomer'               => $site->picustomer,
            'website'                  => $site->website,
            'telp_survey'              => $site->telp_survey,
            'fax_survey'               => $site->fax_survey,
            'id_wilayah'               => $site->id_wilayah,
            'id_wil_oa'                => $site->id_wil_oa,

            /* Grup 2 */
            'max_truck_capacity_min' => $site->max_truck_capacity_min,
            'max_truck_capacity_max' => $site->max_truck_capacity_max,
            'access_notes'           => $site->access_notes,
            'route_costs'            => $site->route_costs,
            'distance_from_depot'    => $site->distance_from_depot,
            'road_condition_photos'  => $site->road_condition_photos,
            'min_vol_kirim'          => $site->min_vol_kirim,
            'rute_lokasi'            => $site->rute_lokasi,
            'note_lokasi'            => $site->note_lokasi,

            /* Grup 3 */
            'site_layout_photos'      => $site->site_layout_photos,
            'unloading_method'        => $site->unloading_method,
            'max_trucks_per_day'      => $site->max_trucks_per_day,
            'unloading_layout_photos' => $site->unloading_layout_photos,
            'unloading_notes'         => $site->unloading_notes,

            /* Grup 4 */
            'storage_type'            => $site->storage_type,
            'storage_type_other'      => $site->storage_type_other,
            'storage_capacity'        => $site->storage_capacity,
            'storage_notes'           => $site->storage_notes,
            'storage_facility_photos' => $site->storage_facility_photos,

            /* Grup 5 */
            'quality_checking_method'     => $site->quality_checking_method,
            'quality_checking_notes'      => $site->quality_checking_notes,
            'quantity_checking_method'    => $site->quantity_checking_method,
            'quantity_checking_notes'     => $site->quantity_checking_notes,
            'measurement_evidence_photos' => $site->measurement_evidence_photos,

            /* Grup 6 */
            'supports_vessel_delivery'        => $site->supports_vessel_delivery,
            'vessel_type'                     => $site->vessel_type?->value,
            'vessel_type_label'               => $site->vessel_type?->label(),
            'vessel_cargo_capacity'           => $site->vessel_cargo_capacity,
            'vessel_unloading_method'         => $site->vessel_unloading_method?->value,
            'vessel_unloading_method_label'   => $site->vessel_unloading_method?->label(),
            'vessel_quantity_checking_method' => $site->vessel_quantity_checking_method?->value,
            'vessel_quantity_checking_method_label' => $site->vessel_quantity_checking_method?->label(),
            'vessel_quantity_checking_notes'  => $site->vessel_quantity_checking_notes,
            'vessel_quality_checking_method'  => $site->vessel_quality_checking_method,
            'vessel_quality_checking_notes'   => $site->vessel_quality_checking_notes,
            'vessel_layout_photos'            => $site->vessel_layout_photos,
            'jetty_type'                      => $site->jetty_type,
            'max_loa'                         => $site->max_loa,
            'min_pbl'                         => $site->min_pbl,
            'draft_lws'                       => $site->draft_lws,
            'jetty_capacity_dwt'              => $site->jetty_capacity_dwt,
            'jetty_permit_info'               => $site->jetty_permit_info,
            'document_requirements'           => $site->document_requirements,

            /* Grup 7 */
            'company_office_photos' => $site->company_office_photos,
            'additional_photos'     => $site->additional_photos,
            'latitude_lokasi'       => $site->latitude_lokasi,
            'longitude_lokasi'      => $site->longitude_lokasi,
            'link_google_maps'      => $site->link_google_maps,
            'coordinates'           => $site->coordinates,

            'approval' => $cycle ? [
                'status'             => $cycle->status->value,
                'status_label'       => $cycle->status->label(),
                'current_step_order' => $cycle->current_step_order,
            ] : null,

            'created_time'    => optional($site->created_time)->toISOString(),
            'lastupdate_time' => optional($site->lastupdate_time)->toISOString(),
        ];
    }
}
