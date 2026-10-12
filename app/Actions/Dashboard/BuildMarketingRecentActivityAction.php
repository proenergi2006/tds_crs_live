<?php

namespace App\Actions\Dashboard;

use App\Models\Customer;
use App\Models\Quotation;
use Carbon\Carbon;

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

        $quotationsCreated = Quotation::where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', 'nomor_penawaran', 'created_at']);

        foreach ($quotationsCreated as $quotation) {
            $activities[] = [
                'type'        => 'penawaran_created',
                'label'       => "Penawaran {$quotation->nomor_penawaran} dibuat",
                'occurred_at' => Carbon::parse($quotation->created_at),
            ];
        }

        $quotationsStatusChanged = Quotation::where('user_id', $userId)
            ->whereColumn('updated_at', '!=', 'created_at')
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'nomor_penawaran', 'status', 'updated_at']);

        foreach ($quotationsStatusChanged as $quotation) {
            $activities[] = [
                'type'        => 'penawaran_status_changed',
                'label'       => "Penawaran {$quotation->nomor_penawaran}: " . ($quotation->status?->label() ?? '-'),
                'occurred_at' => Carbon::parse($quotation->updated_at),
            ];
        }

        usort($activities, fn(array $a, array $b) => $b['occurred_at']->timestamp <=> $a['occurred_at']->timestamp);

        return array_slice($activities, 0, $limit);
    }
}
