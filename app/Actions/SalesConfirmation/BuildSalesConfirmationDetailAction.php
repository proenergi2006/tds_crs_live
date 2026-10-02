<?php

namespace App\Actions\SalesConfirmation;

use App\Actions\PoCustomer\ProcessSalesConfirmationGateAction;
use App\Enums\DocumentApprovalStatus;
use App\Enums\PoCustomerPaymentType;
use App\Models\PoCustomer;

class BuildSalesConfirmationDetailAction
{
    public function execute(PoCustomer $po, float $totalAr): array
    {
        $po->loadMissing([
            'customer.latestApprovedVerification',
            'penawaran.items.produk.ukuran.satuan',
            'penawaran.latestDocumentApproval.steps.templateStep',
            'penawaran.latestDocumentApproval.steps.actor',
            'unblockRequests.requestedBy',
        ]);

        $ppnMultiplier = 1 + ProcessSalesConfirmationGateAction::PPN_RATE;
        $totalNilai    = (float) $po->harga_poc * (float) $po->volume_poc;
        $nilaiOrderPpn = round($totalNilai * $ppnMultiplier, 2);
        $creditLimit   = (float) ($po->customer?->current_credit_limit ?? 0);

        return [
            'poc' => [
                'lampiran_poc'     => $po->lampiran_poc,
                'lampiran_poc_ori' => $po->lampiran_poc_ori,
                'created_by'       => $po->created_by,
                'created_time'     => $po->created_time?->format('Y-m-d H:i:s'),
                'total_nilai'      => round($totalNilai, 2),
                'total_nilai_ppn'  => $nilaiOrderPpn,
            ],
            'penawaran' => $this->penawaranDetail($po, $ppnMultiplier),
            'credit'    => [
                'approved_top'             => $po->customer?->latestApprovedVerification?->approved_top,
                'nilai_order_ppn'          => $nilaiOrderPpn,
                'sisa_limit_setelah_order' => round($creditLimit - $totalAr - $nilaiOrderPpn, 2),
                'gate'                     => $this->gate($po),
            ],
        ];
    }

    private function penawaranDetail(PoCustomer $po, float $ppnMultiplier): array
    {
        $penawaran = $po->penawaran;

        if (!$penawaran) {
            return [];
        }

        $hargaPerM3  = (float) ($penawaran->harga_dasar ?? 0) + (float) ($penawaran->oat ?? 0);
        $totalVolume = (int) $penawaran->items->sum('volume_order');
        $totalHarga  = round($hargaPerM3 * $totalVolume, 2);

        return [
            'id_penawaran'          => $penawaran->id_penawaran,
            'brand'                 => $penawaran->brand?->value,
            'masa_berlaku'          => $penawaran->masa_berlaku,
            'sampai_dengan'         => $penawaran->sampai_dengan,
            'harga_dasar'           => (float) ($penawaran->harga_dasar ?? 0),
            'oat'                   => (float) ($penawaran->oat ?? 0),
            'harga_per_m3'          => $hargaPerM3,
            'tipe_pembayaran'       => $penawaran->tipe_pembayaran,
            'repayment_hari'        => $penawaran->repayment_hari,
            'type_pengiriman'       => $penawaran->type_pengiriman,
            'lokasi_pengiriman'     => $penawaran->lokasi_pengiriman,
            'total_volume'          => $totalVolume,
            'total_harga'           => $totalHarga,
            'total_harga_ppn'       => round($totalHarga * $ppnMultiplier, 2),
            'items'                 => $penawaran->items->map(fn ($item) => [
                'produk'       => $item->produk?->nama_produk,
                'ukuran'       => $item->produk?->ukuran?->nama_ukuran,
                'satuan'       => $item->produk?->ukuran?->satuan?->nama_satuan,
                'persen'       => $item->persen,
                'volume_order' => $item->volume_order,
            ])->values()->all(),
            'approvals'             => $this->penawaranApprovals($po),
        ];
    }

    private function penawaranApprovals(PoCustomer $po): array
    {
        $cycle = $po->penawaran?->latestDocumentApproval;

        if (!$cycle) {
            return [];
        }

        return $cycle->steps->sortBy('step_order')->map(fn ($step) => [
            'step_order' => $step->step_order,
            'step_name'  => $step->templateStep?->step_name,
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
