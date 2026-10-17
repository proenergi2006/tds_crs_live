<?php

namespace App\Http\Controllers\SalesConfirmation;

use App\Actions\PoCustomer\ProcessSalesConfirmationGateAction;
use App\Actions\SalesConfirmation\BuildSalesConfirmationDetailAction;
use App\Actions\SalesConfirmation\DecideSalesConfirmationAction;
use App\Actions\SalesConfirmation\ReturnPoCustomerToMarketingAction;
use App\Actions\SalesConfirmation\SaveSalesConfirmationAction;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PoCustomerScProcessState;
use App\Enums\QuotationVerifierRole;
use App\Enums\SalesConfirmationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesConfirmation\DecideSalesConfirmationRequest as DecideRequest;
use App\Http\Requests\SalesConfirmation\SaveSalesConfirmationRequest as SubmitRequest;
use App\Http\Resources\PoCustomerItemResource;
use App\Http\Responses\ApiResponse;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Models\User;
use App\Services\PublicAttachmentFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesConfirmationController extends Controller
{
    public function index(Request $request): JsonResponse
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
            ->withOrderAggregates()
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
                'total_volume'   => $po->aggregatedTotalVolume(),
                'total_value'    => $po->aggregatedOrderValue(),
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

    public function show(
        Request $request,
        PoCustomer $poc,
        BuildSalesConfirmationDetailAction $detailAction
    ): JsonResponse {
        $this->authorize('viewSalesConfirmation', $poc);

        $user = $request->user();

        $po = $poc->load(['customer.latestApprovedVerification', 'quotation']);

        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer belum lolos gerbang Sales Confirmation.',
                409
            );
        }

        $arnya = CustomerAdminArnya::where('id_customer', $po->id_customer)->first();

        $sc  = SalesConfirmation::where('po_customer_id', $po->id_poc)
            ->with([
                'latestDocumentApproval.steps.templateStep',
                'latestDocumentApproval.steps.actor',
                'latestDocumentApproval.steps.role'
            ])
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
                    'tipe_bayar'       => $po->tipe_bayar?->value,
                    'tipe_bayar_label' => $po->tipe_bayar?->label(),
                    'termin_hari'      => $po->termin_hari,
                    ...$detail['poc'],
                    'items'            => PoCustomerItemResource::collection($po->items->sortBy('id')->values()),
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
                'approval_flow' => $this->formatApprovalFlow($po, $sc, $user),
            ],
            'Detail Sales Confirmation berhasil dimuat.'
        );
    }

    public function submit(
        SubmitRequest $request,
        PoCustomer $poc,
        SaveSalesConfirmationAction $action
    ): JsonResponse {
        $this->authorize('submitSalesConfirmation', $poc);

        $sc = $action->execute(
            $poc->load('customer.latestApprovedVerification'),
            $request->validated(),
            $request->user()->name ?? 'system',
            $request->file('attachments', []),
            $request->input('removed_attachments', [])
        );

        return ApiResponse::success(
            ['id_sales_confirmation' => $sc->id],
            'Sales Confirmation berhasil diajukan untuk approval BM dan OM.'
        );
    }

    public function decide(
        DecideRequest $request,
        PoCustomer $poc,
        DecideSalesConfirmationAction $action
    ): JsonResponse {
        $sc = $poc->salesConfirmation;

        if (! $sc || ! $sc->isAwaitingDecision()) {
            return ApiResponse::error(
                'Sales Confirmation ini tidak sedang menunggu keputusan.',
                409
            );
        }

        $this->authorize('decideSalesConfirmation', $poc);

        $user   = $request->user();
        $status = $request->validated()['decision'] === 'approve'
            ? DocumentApprovalStepStatus::Approved
            : DocumentApprovalStepStatus::Rejected;

        $sc = $action->execute(
            $sc,
            $status,
            $user->id,
            $request->validated()['note'] ?? null,
            $user->name ?? 'system'
        );

        $message = match ($sc->disposisi) {
            SalesConfirmationStatus::PendingOm => 'Sales Confirmation diteruskan ke Operation Manager.',
            SalesConfirmationStatus::Confirmed => 'Sales Confirmation berhasil dikonfirmasi.',
            default                            => 'Sales Confirmation ditolak dan dikembalikan ke Admin Finance.',
        };

        return ApiResponse::success(
            [
                'disposisi'       => $sc->disposisi->value,
                'disposisi_label' => $sc->disposisi->label()
            ],
            $message
        );
    }

    public function returnToMarketing(
        Request $request,
        PoCustomer $poc,
        ReturnPoCustomerToMarketingAction $action
    ): JsonResponse {
        $this->authorize('submitSalesConfirmation', $poc);

        $po = $action->execute($poc, $request->user()->name ?? 'system', $request->ip());

        return ApiResponse::success(
            [
                'status_key'   => $po->status_key,
                'status_label' => $po->status_label
            ],
            'PO Customer berhasil dikembalikan ke Marketing.'
        );
    }

    public function process(
        Request $request,
        PoCustomer $poc,
        ProcessSalesConfirmationGateAction $action
    ): JsonResponse {
        $this->authorize('update', $poc);

        $po = $poc->load(['customer.latestApprovedVerification', 'salesConfirmation', 'quotation']);

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

    private function formatApprovalFlow(PoCustomer $po, ?SalesConfirmation $sc, User $user): ?array
    {
        $cycle = $sc?->latestDocumentApproval;

        if (!$cycle) {
            return null;
        }

        return [
            'current_step_order' => $cycle->current_step_order,
            'can_decide'         => $sc->isAwaitingDecision() && $user->can('decideSalesConfirmation', $po),
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
