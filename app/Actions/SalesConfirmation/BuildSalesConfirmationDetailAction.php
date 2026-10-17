<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\DocumentApprovalStatus;
use App\Enums\PoCustomerPaymentType;
use App\Models\PoCustomer;
use App\Support\Tax;

class BuildSalesConfirmationDetailAction
{
    public function execute(PoCustomer $po, float $totalAr): array
    {
        $po->loadMissing([
            'customer.latestApprovedVerification',
            'quotation.items.product.ukuran.satuan',
            'quotation.latestDocumentApproval.steps.templateStep',
            'quotation.latestDocumentApproval.steps.role',
            'quotation.latestDocumentApproval.steps.actor',
            'unblockRequests.requestedBy',
            'items.quotationItem.product.ukuran.satuan',
            'items.quotationItem.sourceBranch',
        ]);

        $ppnMultiplier = 1 + Tax::PPN_RATE;
        $nilaiOrderPpn = $po->orderValueWithPpn();
        $creditLimit   = (float) ($po->customer?->current_credit_limit ?? 0);

        return [
            'poc' => [
                'lampiran_poc'     => $po->lampiran_poc,
                'lampiran_poc_ori' => $po->lampiran_poc_ori,
                'created_by'       => $po->created_by,
                'created_time'     => $po->created_time?->format('Y-m-d H:i:s'),
                'totals'           => $po->orderTotals(),
            ],
            'quotation' => $this->quotationDetail($po, $ppnMultiplier),
            'credit'    => [
                'approved_top'             => $po->customer?->latestApprovedVerification?->approved_top,
                'nilai_order_ppn'          => $nilaiOrderPpn,
                'sisa_limit_setelah_order' => round($creditLimit - $totalAr - $nilaiOrderPpn, 2),
                'gate'                     => $this->gate($po),
            ],
        ];
    }

    private function quotationDetail(PoCustomer $po, float $ppnMultiplier): array
    {
        $quotation = $po->quotation;

        if (!$quotation) {
            return [];
        }

        $hargaPerM3  = $quotation->weightedFinalPrice();
        $totalVolume = (float) $quotation->items->sum(fn ($item) => (float) $item->quantity);
        $totalHarga  = round($hargaPerM3 * $totalVolume, 2);

        return [
            'id'                    => $quotation->id,
            'brand'                 => $quotation->brand?->value,
            'masa_berlaku'          => $quotation->masa_berlaku,
            'sampai_dengan'         => $quotation->sampai_dengan,
            'dpp'                   => $quotation->dpp(),
            'subtotal_material'     => $quotation->subtotalMaterial(),
            'transport_total'       => $quotation->transportTotal(),
            'harga_per_m3'          => $hargaPerM3,
            'tipe_pembayaran'       => $quotation->tipe_pembayaran,
            'repayment_hari'        => $quotation->repayment_hari,
            'type_pengiriman'       => $quotation->type_pengiriman,
            'lokasi_pengiriman'     => $quotation->lokasi_pengiriman,
            'total_volume'          => $totalVolume,
            'total_harga'           => $totalHarga,
            'total_harga_ppn'       => round($totalHarga * $ppnMultiplier, 2),
            'items'                 => $quotation->items->map(fn ($item) => [
                'produk'       => $item->product?->nama_produk,
                'ukuran'       => $item->product?->ukuran?->nama_ukuran,
                'satuan'       => $item->product?->ukuran?->satuan?->nama_satuan,
                'unit_price'   => (float) $item->unit_price,
                'quantity'     => (float) $item->quantity,
                'line_total'   => (float) $item->line_total,
                'transport_price' => (float) $item->transport_price,
                'final_price'  => $item->finalPrice(),
                'final_total'  => $item->finalTotal(),
            ])->values()->all(),
            'approvals'             => $this->quotationApprovals($po),
        ];
    }

    private function quotationApprovals(PoCustomer $po): array
    {
        $cycle = $po->quotation?->latestDocumentApproval;

        if (!$cycle) {
            return [];
        }

        return $cycle->steps->sortBy('step_order')->map(fn ($step) => [
            'step_order' => $step->step_order,
            'step_name'  => $step->displayName(),
            'actor_name' => $step->actor?->name,
            'acted_at'   => $step->acted_at?->toISOString(),
            'status'     => $step->status?->value,
        ])->values()->all();
    }

    private function gate(PoCustomer $po): array
    {
        if ($po->tipe_bayar !== PoCustomerPaymentType::Credit) {
            return ['type' => 'cbd_cod', 'unblock' => null];
        }

        $unblock = $po->unblockRequests
            ->where('status', DocumentApprovalStatus::Approved)
            ->sortByDesc('id')
            ->first();

        if (!$unblock) {
            return ['type' => 'auto', 'unblock' => null];
        }

        return [
            'type'    => 'unblock',
            'unblock' => [
                'requested_by_name' => $unblock->requestedBy?->name,
                'requested_at'      => $unblock->created_at?->toISOString(),
                'reason'            => $unblock->reason,
            ],
        ];
    }
}
