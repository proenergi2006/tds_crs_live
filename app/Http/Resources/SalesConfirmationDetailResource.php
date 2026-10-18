<?php

namespace App\Http\Resources;

use App\Enums\QuotationVerifierRole;
use App\Enums\SalesConfirmationStatus;
use App\Models\CustomerAdminArnya;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Services\PublicAttachmentFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesConfirmationDetailResource extends JsonResource
{
    public function __construct(
        $resource,
        private readonly array $detail,
        private readonly ?CustomerAdminArnya $arnya,
        private readonly ?SalesConfirmation $salesConfirmation,
        private readonly ?SalesConfirmationApproval $approval
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $sc        = $this->salesConfirmation;
        $apr       = $this->approval;
        $arnya     = $this->arnya;
        $quotation = $this->quotation;

        $disposisi = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
        $when      = $sc?->lastupdate_time
            ?? $sc?->created_time
            ?? $this->created_time
            ?? now();

        $marketing = $quotation?->marketing_name
            ?? $quotation?->marketing
            ?? $quotation?->created_by
            ?? ($this->created_by ?? '-');

        return [
            'poc' => [
                'id_poc'           => $this->id_poc,
                'nomor_poc'        => $this->nomor_poc,
                'tanggal_poc'      => $this->tanggal_poc,
                'supply_date'      => $this->supply_date,
                'tipe_bayar'       => $this->tipe_bayar?->value,
                'tipe_bayar_label' => $this->tipe_bayar?->label(),
                'termin_hari'      => $this->termin_hari,
                ...$this->detail['poc'],
                'items'            => PoCustomerItemResource::collection($this->items->sortBy('id')->values()),
            ],
            'customer' => [
                'id_customer'   => $this->customer?->id_customer,
                'customer_code' => $this->customer?->customer_code,
                'company_name'  => $this->customer?->company_name,
                'credit_limit'  => (float) ($this->customer?->current_credit_limit ?? 0),
            ],
            'quotation' => [
                'nomor_penawaran' => $quotation?->nomor_penawaran ?? '-',
                'marketing_name'  => $marketing,
                ...$this->detail['quotation'],
            ],
            'credit' => $this->detail['credit'],
            'arnya' => [
                'id_arnya'            => $arnya?->id_arnya,
                'id_customer'         => $this->id_customer,
                'outstanding_current' => (float) ($arnya->outstanding_current ?? 0),
                'overdue_1_30'        => (float) ($arnya->overdue_1_30 ?? 0),
                'overdue_31_60'       => (float) ($arnya->overdue_31_60 ?? 0),
                'overdue_61_90'       => (float) ($arnya->overdue_61_90 ?? 0),
                'overdue_90_plus'     => (float) ($arnya->overdue_90_plus ?? 0),
                'total_ar'            => (float) ($arnya?->total_ar ?? 0),
            ],
            'sc' => [
                'disposisi'       => $disposisi,
                'disposisi_label' => SalesConfirmationStatus::tryFrom($disposisi)?->label() ?? 'Draft',
                'disposisi_time'  => Carbon::parse($when)->format('Y-m-d H:i:s'),
            ],
            'sc_header' => $sc ? [
                'credit_limit' => (float) $sc->credit_limit,
                'not_yet'      => (float) $sc->not_yet,
                'ov_up_07'     => (float) $sc->ov_up_07,
                'ov_under_30'  => (float) $sc->ov_under_30,
                'ov_under_60'  => (float) $sc->ov_under_60,
                'ov_under_90'  => (float) $sc->ov_under_90,
                'ov_up_90'     => (float) $sc->ov_up_90,
            ] : null,
            'approval' => $apr ? [
                'adm_result'      => (int) $apr->adm_result,
                'adm_summary'     => $apr->adm_summary,
                'adm_result_date' => $apr->adm_result_date,
                'adm_pic'         => $apr->adm_pic,
                'adm_attachments' => PublicAttachmentFormatter::format($apr->adm_attachments),
            ] : null,
            'approval_flow' => $this->approvalFlow($request),
        ];
    }

    private function approvalFlow(Request $request): ?array
    {
        $cycle = $this->salesConfirmation?->latestDocumentApproval;

        if (! $cycle) {
            return null;
        }

        return [
            'current_step_order' => $cycle->current_step_order,
            'can_decide'         => $this->salesConfirmation->isAwaitingDecision()
                && $request->user()->can('decideSalesConfirmation', $this->resource),
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
