<?php

namespace App\Http\Controllers\SalesConfirmation;

use App\Actions\PoCustomer\ProcessSalesConfirmationGateAction;
use App\Actions\SalesConfirmation\BuildSalesConfirmationDetailAction;
use App\Actions\SalesConfirmation\DecideSalesConfirmationAction;
use App\Actions\SalesConfirmation\ReturnPoCustomerToMarketingAction;
use App\Actions\SalesConfirmation\SaveSalesConfirmationAction;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\PoCustomerScProcessState;
use App\Enums\SalesConfirmationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesConfirmation\DecideSalesConfirmationRequest as DecideRequest;
use App\Http\Requests\SalesConfirmation\SaveSalesConfirmationRequest as SubmitRequest;
use App\Http\Resources\SalesConfirmationDetailResource;
use App\Http\Resources\SalesConfirmationIndexResource;
use App\Http\Responses\ApiResponse;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesConfirmationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesConfirmation::class);

        $validated = $request->validate([
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'search'    => ['nullable', 'string', 'max:150'],
            'disposisi' => ['nullable', 'integer', Rule::enum(SalesConfirmationStatus::class)],
        ]);

        $perPage  = (int) ($validated['per_page'] ?? 25);
        $search   = trim($validated['search'] ?? '');
        $scFilter = $validated['disposisi'] ?? null;

        $query = PoCustomer::query()
            ->with([
                'customer:id_customer,customer_code,company_name',
                'quotation',
                'salesConfirmation:id,po_customer_id,disposisi,lastupdate_time,created_time',
                'activeUnblockRequestRelation.latestDocumentApproval',
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

        if ($scFilter !== null) {
            $query->whereHas('salesConfirmation', function ($q) use ($scFilter) {
                $q->where('disposisi', (int) $scFilter);
            });
        }

        $data = $query->orderByDesc('tanggal_poc')
                      ->orderByDesc('created_time')
                      ->paginate($perPage);

        return ApiResponse::success(
            SalesConfirmationIndexResource::collection($data),
            'Daftar Sales Confirmation berhasil dimuat.'
        );
    }

    public function show(PoCustomer $poc, BuildSalesConfirmationDetailAction $detailAction): JsonResponse
    {
        $this->authorize('viewSalesConfirmation', $poc);

        $po = $poc->load(['customer.latestApprovedVerification', 'quotation']);

        if ($po->sc_process_state !== PoCustomerScProcessState::Cleared) {
            return ApiResponse::error(
                'PO Customer belum lolos gerbang Sales Confirmation.',
                409
            );
        }

        $arnya = CustomerAdminArnya::where('id_customer', $po->id_customer)->first();

        $sc = SalesConfirmation::where('po_customer_id', $po->id_poc)
            ->with([
                'latestDocumentApproval.steps.templateStep',
                'latestDocumentApproval.steps.actor',
                'latestDocumentApproval.steps.role'
            ])
            ->first();
        $approval = $sc ? SalesConfirmationApproval::where('id_sales', $sc->id)->first() : null;

        $detail = $detailAction->execute($po, (float) ($arnya?->total_ar ?? 0));

        return ApiResponse::success(
            new SalesConfirmationDetailResource($po, $detail, $arnya, $sc, $approval),
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
}
