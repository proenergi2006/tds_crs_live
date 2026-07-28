<?php

namespace App\Actions\Dashboard;

use App\Models\Customer;
use App\Models\Penawaran;
use Carbon\Carbon;

/**
 * Menggabungkan beberapa sumber event jadi satu feed "recent activity" untuk
 * Dashboard Marketing, terurut waktu terbaru lebih dulu.
 *
 * customer_link_generated (CustomerVerification) BELUM ikut digabung di sini --
 * tabel customer_verifications tidak punya kolom created_at/timestamp apa pun
 * untuk menandai kapan link onboarding digenerate (lihat laporan Task 1 ke
 * Engineer). Menyusul setelah keputusan schema diambil.
 */
class BuildMarketingRecentActivityAction
{
    public function execute(int $userId, int $limit = 10): array
    {
        $activities = [];

        $customers = Customer::where('id_user', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get(['id_customer', 'company_name', 'created_at']);

        foreach ($customers as $customer) {
            $activities[] = [
                'type'        => 'customer_created',
                'label'       => "Customer {$customer->company_name} dibuat",
                'occurred_at' => Carbon::parse($customer->created_at),
            ];
        }

        $penawaranCreated = Penawaran::where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get(['id_penawaran', 'nomor_penawaran', 'created_at']);

        foreach ($penawaranCreated as $penawaran) {
            $activities[] = [
                'type'        => 'penawaran_created',
                'label'       => "Penawaran {$penawaran->nomor_penawaran} dibuat",
                'occurred_at' => Carbon::parse($penawaran->created_at),
            ];
        }

        // whereColumn mencegah penawaran yang belum pernah diubah statusnya muncul
        // dobel sebagai "dibuat" DAN "berubah status" di saat bersamaan.
        $penawaranStatusChanged = Penawaran::where('user_id', $userId)
            ->whereColumn('updated_at', '!=', 'created_at')
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id_penawaran', 'nomor_penawaran', 'status', 'updated_at']);

        foreach ($penawaranStatusChanged as $penawaran) {
            $activities[] = [
                'type'        => 'penawaran_status_changed',
                'label'       => "Penawaran {$penawaran->nomor_penawaran}: " . $this->statusLabel($penawaran->status),
                'occurred_at' => Carbon::parse($penawaran->updated_at),
            ];
        }

        usort($activities, fn(array $a, array $b) => $b['occurred_at']->timestamp <=> $a['occurred_at']->timestamp);

        return array_slice($activities, 0, $limit);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft'                   => 'Draft',
            'waiting_branch_manager'  => 'Menunggu Persetujuan BM',
            'approved_bm'             => 'Disetujui BM, Menunggu OM',
            'approved_om'             => 'Disetujui Penuh',
            'rejected_bm'             => 'Ditolak BM',
            'rejected_om'             => 'Ditolak OM',
            default                   => $status,
        };
    }
}
