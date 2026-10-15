<?php

namespace App\Http\Controllers\PoCustomer;

use App\Actions\PoCustomer\ProcessSalesConfirmationGateAction;
use App\Actions\PoCustomer\UpdatePoCustomerAction;
use App\Actions\SalesConfirmation\BuildSalesConfirmationDetailAction;
use App\Actions\SalesConfirmation\DecideSalesConfirmationAction;
use App\Actions\SalesConfirmation\ReturnPoCustomerToMarketingAction;
use App\Actions\SalesConfirmation\SaveSalesConfirmationAction;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PoCustomerScProcessState;
use App\Enums\QuotationBrand;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Enums\SalesConfirmationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PoCustomer\UpdatePoCustomerRequest;
use App\Http\Requests\SalesConfirmation\DecideSalesConfirmationRequest;
use App\Http\Requests\SalesConfirmation\SaveSalesConfirmationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\PoCustomerPlan;
use App\Models\Quotation;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use App\Services\PublicAttachmentFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PoCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PoCustomer::class);

        $user = $request->user();

        $perPage = min((int) $request->query('per_page', 10), 100);
        $search  = $request->query('search');

        $query = PoCustomer::with(['customer:id_customer,customer_code,company_name', 'quotation', 'salesConfirmation']);

        if (! PoCustomer::readsAllPosAs($user)) {
            $brands = QuotationBrand::viewableAnyBy($user);

            $query->whereHas('quotation', fn (Builder $q) => $q->where(
                fn (Builder $w) => $w->whereIn('brand', $brands)->orWhere('user_id', $user->id)
            ));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_poc', 'ilike', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_time', 'desc')->paginate($perPage);

        $data->getCollection()->each(function (PoCustomer $po) {
            $po->setAttribute('status_key', $po->status_key);
            $po->setAttribute('status_label', $po->status_label);
        });

        return ApiResponse::success(
            JsonResource::collection($data),
            'Daftar PO Customer berhasil dimuat.'
        );
    }

    // NOTE: pengecualian route model binding kategori 2 — quotation dan customer diambil dari id di body request.
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PoCustomer::class);

        $validated = $request->validate([
            'id_customer'      => 'required|exists:customers,id_customer',
            'quotation_id'     => 'required|exists:quotations,id',
            'nomor_poc'        => 'required|string|max:50',
            'tanggal_poc'      => 'required|date',
            'supply_date'      => 'required|date',
            'volume_poc'       => 'required|integer',
            'produk_poc'       => 'nullable|integer',
            'lampiran_poc'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'tipe_bayar'       => ['required', Rule::in(['CBD', 'COD', 'CREDIT'])],
            'termin_hari'      => ['required_if:tipe_bayar,CREDIT', 'nullable', 'integer', 'min:1'],
        ]);

        $quotation = Quotation::with('items')->find($validated['quotation_id']);

        $this->authorize('createFrom', [PoCustomer::class, $quotation]);

        if ((int) $quotation->customer_id !== (int) $validated['id_customer']) {
            throw ValidationException::withMessages([
                'id_customer' => ['Customer tidak sesuai dengan penawaran.'],
            ]);
        }

        if ($request->user()->cannot('view', $quotation)) {
            throw ValidationException::withMessages([
                'quotation_id' => ['Penawaran ini bukan milik Anda.'],
            ]);
        }

        if ($quotation->status !== QuotationStatus::Approved) {
            throw ValidationException::withMessages([
                'quotation_id' => ['PO Customer hanya bisa dibuat dari Penawaran yang sudah disetujui.'],
            ]);
        }

        if (! $quotation->isValidOn(Carbon::today())) {
            throw ValidationException::withMessages([
                'quotation_id' => ["Penawaran tidak berlaku hari ini (masa berlaku {$quotation->validityPeriodLabel()}). Gunakan penawaran periode berjalan."],
            ]);
        }

        $customer = Customer::find($validated['id_customer']);
        if (! $customer?->is_verified) {
            throw ValidationException::withMessages([
                'id_customer' => ['PO Customer hanya bisa dibuat untuk customer yang sudah terverifikasi (KYC).'],
            ]);
        }

        $data = Arr::except($validated, ['lampiran_poc']);

        $data['termin_hari'] = $validated['tipe_bayar'] === 'CREDIT' ? $validated['termin_hari'] : null;

        $data['harga_poc'] = round($quotation->weightedFinalPrice(), 4);

        if ($request->hasFile('lampiran_poc')) {
            $file = $request->file('lampiran_poc');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('lampiran_po', $filename, 'public');

            $data['lampiran_poc'] = $path;
            $data['lampiran_poc_ori'] = $file->getClientOriginalName();
        } else {
            $data['lampiran_poc'] = '';
            $data['lampiran_poc_ori'] = '';
        }

        $data['created_time'] = now();
        $data['created_by'] = auth()->user()->name ?? 'system';
        $data['created_ip'] = $request->ip();

        $po = PoCustomer::create($data);

        return ApiResponse::success(
            new JsonResource($po),
            'PO Customer berhasil dibuat.',
            201
        );
    }

    public function update(
        UpdatePoCustomerRequest $request,
        PoCustomer $customerPo,
        UpdatePoCustomerAction $action
    ): JsonResponse {
        $this->authorize('update', $customerPo);

        $po = $customerPo->load('quotation');

        if ($po->is_locked) {
            return ApiResponse::error(
                'PO Customer tidak bisa diubah karena sudah masuk proses Sales Confirmation.',
                409
            );
        }

        $po = $action->execute(
            $po,
            $request->validated(),
            $request->file('lampiran_poc'),
            $request->user()->name ?? 'system',
            $request->ip()
        );

        $po->setAttribute('status_key', $po->status_key);
        $po->setAttribute('status_label', $po->status_label);
        $po->setAttribute('is_locked', $po->is_locked);

        return ApiResponse::success(
            new JsonResource($po),
            'PO Customer berhasil diperbarui.'
        );
    }

    public function show(Request $request, PoCustomer $customerPo): JsonResponse
    {
        $this->authorize('view', $customerPo);

        $po = $customerPo->load([
            'customer',
            'quotation.items.product.jenis',
            'quotation.items.product.ukuran',
            'salesConfirmation',
        ]);

        $po->append(['status_key', 'status_label', 'tipe_bayar_label']);
        return ApiResponse::success(
            new JsonResource($po),
            'Detail PO Customer berhasil dimuat.'
        );
    }

    public function destroy(PoCustomer $customerPo): JsonResponse
    {
        $this->authorize('update', $customerPo);

        $po = $customerPo->load('quotation');

        if ($po->is_locked) {
            return ApiResponse::error(
                'PO Customer tidak bisa dihapus karena sudah masuk proses Sales Confirmation.',
                409
            );
        }

        if ($po->lampiran_poc && Storage::disk('public')->exists($po->lampiran_poc)) {
            Storage::disk('public')->delete($po->lampiran_poc);
        }

        $po->delete();

        return ApiResponse::success(
            null,
            'PO Customer berhasil dihapus.'
        );
    }

    public function salesConfirmation(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesConfirmation::class);

        $perPage  = min((int) $request->query('per_page', 25), 100);
        $search   = trim((string) $request->query('search', ''));
        $scFilter = $request->query('disposisi');

        $query = PoCustomer::query()
            ->with([
                'customer:id_customer,customer_code,company_name',
                'quotation',
                'salesConfirmation:id,po_customer_id,disposisi,lastupdate_time,created_time',
            ])
            ->whereIn('sc_process_state', [
                PoCustomerScProcessState::Cleared->value,
                PoCustomerScProcessState::Blocked->value,
            ])
            ->salesConfirmationVisibleTo($request->user());

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('nomor_poc', 'ILIKE', $like)
                  ->orWhereHas('customer', fn($qc) => $qc
                        ->where('company_name', 'ILIKE', $like)
                        ->orWhere('customer_code', 'ILIKE', $like))
                  ->orWhereHas('quotation', fn($qp) => $qp
                        ->where('nomor_penawaran', 'ILIKE', $like));
            });
        }

        if ($scFilter !== null && $scFilter !== '') {
            $query->whereHas('salesConfirmation', function ($q) use ($scFilter) {
                $q->where('disposisi', (int) $scFilter);
            });
        }

        $data = $query->orderByDesc('tanggal_poc')
                      ->orderByDesc('created_time')
                      ->paginate($perPage);

        $data->getCollection()->transform(function ($po) {
            $sc = $po->salesConfirmation;
            $activeUnblock = $po->activeUnblockRequest();

            $disposisi = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
            $label     = SalesConfirmationStatus::tryFrom($disposisi)?->label() ?? 'Draft';
            $when = $sc->lastupdate_time ?? $sc->created_time ?? $po->created_time;

            $marketing = $po->quotation->marketing_name
                ?? $po->quotation->marketing
                ?? $po->quotation->created_by
                ?? ($po->created_by ?? '-');

            return [
                'id_poc'         => $po->id_poc,
                'nomor_poc'      => $po->nomor_poc,
                'tanggal_poc'    => $po->tanggal_poc,
                'volume_poc'     => (float) ($po->volume_poc ?? 0),
                'harga_poc'      => (float) ($po->harga_poc ?? 0),
                'customer'       => [
                    'customer_code' => $po->customer->customer_code ?? null,
                    'company_name'  => $po->customer->company_name ?? '-',
                ],
                'quotation'      => [
                    'nomor_penawaran' => $po->quotation->nomor_penawaran ?? '-',
                ],
                'marketing_name' => $marketing,

                'disposisi'      => $disposisi,
                'disposisi_text' => $label,
                'disposisi_time' => $when ? Carbon::parse($when)->format('Y-m-d H:i:s') : null,

                'sc_process_state' => $po->sc_process_state?->value,
                'active_unblock_request' => $activeUnblock ? [
                    'id'                 => $activeUnblock->id,
                    'current_step_order' => $activeUnblock->latestDocumentApproval?->current_step_order,
                ] : null,
            ];
        });

        return ApiResponse::success(
            JsonResource::collection($data),
            'Daftar Sales Confirmation berhasil dimuat.'
        );
    }

    public function showSalesConfirmation(
        PoCustomer $poc,
        BuildSalesConfirmationDetailAction $detailAction
    ): JsonResponse {
        $this->authorize('viewSalesConfirmation', $poc);

        $user = auth()->user();

        $po = $poc->load(['customer.latestApprovedVerification', 'quotation']);

        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer belum lolos gerbang Sales Confirmation.',
                409
            );
        }

        $arnya = CustomerAdminArnya::where('id_customer', $po->id_customer)->first();

        $sc  = SalesConfirmation::where('po_customer_id', $po->id_poc)
            ->with(['latestDocumentApproval.steps.templateStep', 'latestDocumentApproval.steps.actor', 'latestDocumentApproval.steps.role'])
            ->first();
        $apr = $sc ? SalesConfirmationApproval::where('id_sales', $sc->id)->first() : null;

        $scDisp  = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
        $scLabel = SalesConfirmationStatus::tryFrom($scDisp)?->label() ?? 'Draft';
        $scWhen = $sc->lastupdate_time
            ?? $sc->created_time
            ?? $po->created_time
            ?? now();

        $marketing = $po->quotation?->marketing_name
            ?? $po->quotation?->marketing
            ?? $po->quotation?->created_by
            ?? ($po->created_by ?? '-');

        $totalAr = (float) ($arnya?->total_ar ?? 0);
        $detail  = $detailAction->execute($po, $totalAr);

        return ApiResponse::success(
            [
                'poc' => [
                    'id_poc'           => $po->id_poc,
                    'nomor_poc'        => $po->nomor_poc,
                    'tanggal_poc'      => $po->tanggal_poc,
                    'supply_date'      => $po->supply_date,
                    'volume_poc'       => (float)($po->volume_poc ?? 0),
                    'harga_poc'        => (float)($po->harga_poc ?? 0),
                    'tipe_bayar'       => $po->tipe_bayar?->value,
                    'tipe_bayar_label' => $po->tipe_bayar?->label(),
                    'termin_hari'      => $po->termin_hari,
                    ...$detail['poc'],
                ],
                'customer' => [
                    'id_customer'   => $po->customer?->id_customer,
                    'customer_code' => $po->customer?->customer_code,
                    'company_name'  => $po->customer?->company_name,
                    'credit_limit'  => (float) ($po->customer?->current_credit_limit ?? 0),
                ],
                'quotation' => [
                    'nomor_penawaran' => $po->quotation?->nomor_penawaran ?? '-',
                    'marketing_name'  => $marketing,
                    ...$detail['quotation'],
                ],
                'credit' => $detail['credit'],
                'arnya' => [
                    'id_arnya'            => $arnya?->id_arnya,
                    'id_customer'         => $po->id_customer,
                    'outstanding_current' => (float) ($arnya->outstanding_current ?? 0),
                    'overdue_1_30'        => (float) ($arnya->overdue_1_30 ?? 0),
                    'overdue_31_60'       => (float) ($arnya->overdue_31_60 ?? 0),
                    'overdue_61_90'       => (float) ($arnya->overdue_61_90 ?? 0),
                    'overdue_90_plus'     => (float) ($arnya->overdue_90_plus ?? 0),
                    'total_ar'            => (float) ($arnya?->total_ar ?? 0),
                ],
                'sc' => [
                    'disposisi'       => $scDisp,
                    'disposisi_label' => $scLabel,
                    'disposisi_time'  => Carbon::parse($scWhen)->format('Y-m-d H:i:s'),
                ],
                'sc_header' => $sc ? [
                    'credit_limit'    => (float)$sc->credit_limit,
                    'not_yet'         => (float)$sc->not_yet,
                    'ov_up_07'        => (float)$sc->ov_up_07,
                    'ov_under_30'     => (float)$sc->ov_under_30,
                    'ov_under_60'     => (float)$sc->ov_under_60,
                    'ov_under_90'     => (float)$sc->ov_under_90,
                    'ov_up_90'        => (float)$sc->ov_up_90,
                ] : null,
                'approval' => $apr ? [
                    'adm_result'      => (int)$apr->adm_result,
                    'adm_summary'     => $apr->adm_summary,
                    'adm_result_date' => $apr->adm_result_date,
                    'adm_pic'         => $apr->adm_pic,
                    'adm_attachments' => PublicAttachmentFormatter::format($apr->adm_attachments),
                ] : null,
                'bm_approval' => $this->formatBmApproval($po, $sc, $user),
            ],
            'Detail Sales Confirmation berhasil dimuat.'
        );
    }

    public function saveSalesConfirmation(
        SaveSalesConfirmationRequest $request,
        PoCustomer $poc,
        SaveSalesConfirmationAction $action
    ): JsonResponse {
        $this->authorize('submitSalesConfirmation', $poc);

        $po = $poc->load('customer.latestApprovedVerification');

        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer belum lolos gerbang Sales Confirmation.',
                409
            );
        }

        $existingSc = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();

        if ($existingSc?->disposisi === SalesConfirmationStatus::PendingBm) {
            return ApiResponse::error(
                'Sales Confirmation ini sudah diajukan dan sedang menunggu keputusan BM.',
                409
            );
        }

        if ($existingSc?->disposisi === SalesConfirmationStatus::Confirmed) {
            return ApiResponse::error(
                'Sales Confirmation ini sudah dikonfirmasi.',
                409
            );
        }

        $sc = $action->execute(
            $po,
            $request->validated(),
            $request->user()->name ?? 'system',
            $request->file('attachments', []),
            $request->input('removed_attachments', [])
        );

        return ApiResponse::success(
            ['id_sales_confirmation' => $sc->id],
            'Sales Confirmation berhasil diajukan ke Branch Manager.'
        );
    }

    public function decideSalesConfirmationBm(
        DecideSalesConfirmationRequest $request,
        PoCustomer $poc,
        DecideSalesConfirmationAction $action
    ): JsonResponse {
        $user = $request->user();
        $po = $poc;
        $sc = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();

        if (!$sc || $sc->disposisi !== SalesConfirmationStatus::PendingBm) {
            return ApiResponse::error(
                'Sales Confirmation ini tidak sedang menunggu keputusan BM.',
                409
            );
        }

        $service = new DocumentApprovalService();
        $cycle = $service->activeCycle($sc);

        if (!$cycle || (int) $cycle->current_step_order !== 1) {
            return ApiResponse::error(
                'Belum giliran BM memutuskan.',
                409
            );
        }

        $this->authorize('decideSalesConfirmation', $poc);

        $status = $request->validated()['decision'] === 'approve'
            ? DocumentApprovalStepStatus::Approved
            : DocumentApprovalStepStatus::Rejected;

        $sc = $action->execute($sc, $status, $user->id, $request->validated()['note'] ?? null, $user->name ?? 'system');

        $message = $status === DocumentApprovalStepStatus::Approved
            ? 'Sales Confirmation berhasil dikonfirmasi.'
            : 'Sales Confirmation ditolak dan dikembalikan ke Admin Finance.';

        return ApiResponse::success(
            ['disposisi' => Str::snake($sc->disposisi->name)],
            $message
        );
    }

    public function returnSalesConfirmationToMarketing(
        Request $request,
        PoCustomer $poc,
        ReturnPoCustomerToMarketingAction $action
    ): JsonResponse {
        $this->authorize('submitSalesConfirmation', $poc);

        $user = $request->user();
        $po = $poc;

        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer belum lolos gerbang Sales Confirmation.',
                409
            );
        }

        $sc = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();

        if ($sc?->disposisi === SalesConfirmationStatus::PendingBm) {
            return ApiResponse::error(
                'Sales Confirmation sedang menunggu keputusan BM.',
                409
            );
        }

        if ($sc?->disposisi === SalesConfirmationStatus::Confirmed) {
            return ApiResponse::error(
                'Sales Confirmation sudah dikonfirmasi.',
                409
            );
        }

        $po = $action->execute($po, $user->name ?? 'system', $request->ip());

        return ApiResponse::success(
            [
                'status_key'   => $po->status_key,
                'status_label' => $po->status_label,
            ],
            'PO Customer berhasil dikembalikan ke Marketing.'
        );
    }

    public function processSalesConfirmation(
        Request $request,
        PoCustomer $poc,
        ProcessSalesConfirmationGateAction $action
    ): JsonResponse {
        $this->authorize('update', $poc);

        $po = $poc->load(['customer.latestApprovedVerification', 'salesConfirmation', 'quotation']);

        if ($po->sc_process_state === PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer sudah lolos gerbang Sales Confirmation.',
                409
            );
        }

        if ($po->sc_process_state === PoCustomerScProcessState::Blocked && $po->activeUnblockRequest() !== null) {
            return ApiResponse::error(
                'Ada pengajuan Unblock yang masih diproses. Selesaikan dulu.',
                409
            );
        }

        $gate = $action->execute($po, $request->user()->name ?? 'system', $request->ip());

        $po->refresh();

        return ApiResponse::success(
            [
                'sc_process_state' => $po->sc_process_state?->value,
                'status_key'       => $po->status_key,
                'status_label'     => $po->status_label,
                'is_locked'        => $po->is_locked,
                'gate'             => [
                    'nilai_order'          => $gate['nilai_order'],
                    'current_credit_limit' => $gate['current_credit_limit'],
                    'exposure'             => $gate['exposure'],
                    'headroom'             => $gate['headroom'],
                ],
            ],
            "Proses gerbang Sales Confirmation selesai: {$po->status_label}."
        );
    }

    public function updateNomorPo(Request $r, PoCustomer $poc): JsonResponse
    {
        $this->authorize('update', $poc);

        $po = $poc->load('quotation');

        $r->validate(['nomor_poc' => 'required|string|max:50']);

        if ($po->is_locked) {
            return ApiResponse::error(
                'Nomor PO tidak bisa diubah karena PO sudah masuk proses Sales Confirmation.',
                409
            );
        }

        $po->nomor_poc = $r->nomor_poc;
        $po->save();
        return ApiResponse::success(
            null,
            'Nomor PO berhasil diperbarui.'
        );
    }

    public function getPoPlan(PoCustomer $poc): JsonResponse
    {
        $this->authorize('view', $poc);

        $po = $poc->load(['customer','quotation']);

        $p = $po->quotation;
        $awal  = $p->periode_awal  ?? $p->start_date ?? $p->tanggal_mulai ?? null;
        $akhir = $p->periode_akhir ?? $p->end_date   ?? $p->tanggal_selesai ?? null;
        $periode = ($awal && $akhir)
            ? Carbon::parse($awal)->format('d/m/Y').' - '.Carbon::parse($akhir)->format('d/m/Y')
            : '-';

        $volume = (int)($po->volume_poc ?? 0);
        $harga  = (float)($po->harga_poc ?? 0);

        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        $header = [
            'doc_code'             => 'PO-'.str_pad($po->id_poc, 5, '0', STR_PAD_LEFT),
            'penawaran_code'       => $p->nomor_penawaran ?? '-',
            'periode'              => $periode,
            'customer_name'        => $po->customer->company_name ?? '-',
            'tipe_bayar_label'     => $po->tipe_bayar_label,
            'termin_hari'          => $po->termin_hari,
            'nomor_poc'            => $po->nomor_poc,
            'tanggal_poc'          => $po->tanggal_poc,
            'supply_date'          => $po->supply_date,
            'produk'               => $po->produk_poc ?? ($p->produk_name ?? '-'),
            'harga_per_liter'      => $harga,
            'jumlah_volume_liter'  => $volume,
            'total_order_rp'       => $volume * $harga,

            'total_liter'          => $volume,
            'shipped_liter'        => (int)$shipped,
            'book_remaining_liter' => max(0, $volume - (int)$shipped),
            'close_po_liter'       => 0,
        ];

        $items = PoCustomerPlan::where('id_poc', $po->id_poc)
            ->orderBy('created_time')
            ->get()
            ->map(function ($r) {
                $statusMap = [0=>'Terdaftar',1=>'Masuk PR',2=>'Reschedule',3=>'Pending'];
                return [
                    'id'           => $r->id_plan,
                    'issued_at'    => $r->created_time,
                    'ship_date'    => $r->tanggal_kirim,
                    'address'      => $r->status_jadwal,
                    'volume_liter' => (int)$r->volume_kirim,
                    'real_liter'   => (int)$r->realisasi_kirim,
                    'status'       => $statusMap[$r->status_plan] ?? '-',
                    'notes'        => $r->catatan_reschedule,
                ];
            });

        return ApiResponse::success(
            ['header' => $header, 'items' => $items],
            'Plan pengiriman PO berhasil dimuat.'
        );
    }

    public function createPoPlan(Request $request, PoCustomer $poc): JsonResponse
    {
        $this->authorize('update', $poc);

        $request->validate([
            'ship_date'    => 'required|date',
            'address'      => 'nullable|string',
            'volume_kirim' => 'required|integer|min:1',
            'notes'        => 'nullable|string',
            'is_urgent'    => 'nullable|boolean',
        ]);

        $po = $poc;

        $plan = PoCustomerPlan::create([
            'id_poc'           => $po->id_poc,
            'id_lcr'           => 0,
            'tanggal_kirim'    => $request->ship_date,
            'volume_kirim'     => (int)$request->volume_kirim,
            'realisasi_kirim'  => 0,
            'is_urgent'        => $request->boolean('is_urgent') ? 1 : 0,
            'status_plan'      => 0,
            'status_jadwal'    => trim(($request->address ? "Alamat: ".$request->address."\n" : '') . ($request->notes ?? '')),
            'catatan_reschedule' => null,
            'ask_approval'     => 0,
            'is_approved'      => 1,
            'created_time'     => now(),
            'created_ip'       => $request->ip(),
            'created_by'       => auth()->user()->name ?? 'system',
        ]);

        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return ApiResponse::success(
            [
                'id'      => $plan->id_plan,
                'header_patch' => [
                    'shipped_liter'        => (int)$shipped,
                    'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
                ],
            ],
            'Plan pengiriman berhasil disimpan.',
            201
        );
    }

    public function deletePoPlan(PoCustomer $poc, PoCustomerPlan $plan): JsonResponse
    {
        $this->authorize('update', $poc);

        $plan->delete();

        $po = $poc;
        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return ApiResponse::success(
            [
                'header_patch' => [
                    'shipped_liter'        => (int)$shipped,
                    'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
                ],
            ],
            'Plan pengiriman berhasil dihapus.'
        );
    }

    private function formatBmApproval(PoCustomer $po, ?SalesConfirmation $sc, User $user): ?array
    {
        $cycle = $sc?->latestDocumentApproval;

        if (!$cycle) {
            return null;
        }

        return [
            'current_step_order' => $cycle->current_step_order,
            'can_decide'         => $sc->disposisi === SalesConfirmationStatus::PendingBm
                && $user->can('decideSalesConfirmation', $po),
            'steps'              => $cycle->steps->sortBy('step_order')->map(fn ($step) => [
                'step_order'    => $step->step_order,
                'step_name'     => $step->templateStep?->step_name
                    ?? QuotationVerifierRole::tryFrom((int) $step->id_role)?->label()
                    ?? $step->role?->name,
                'status'        => $step->status?->value,
                'actor_name'    => $step->actor->name ?? null,
                'acted_at'      => optional($step->acted_at)->toISOString(),
                'decision_note' => $step->decision_note,
            ])->values(),
        ];
    }
}
