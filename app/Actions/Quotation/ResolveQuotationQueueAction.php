<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStatus;
use App\Enums\PriceTier;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use App\Support\Approval\QuotationVerifierSummary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ResolveQuotationQueueAction
{
    public function execute(array $brands, array $roleIds, ?string $search, int $perPage, User $user, ?PriceTier $priceTier = null): LengthAwarePaginator
    {
        if ($roleIds === [] || $brands === []) {
            return new Paginator([], 0, $perPage);
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $needsAction  = "CASE WHEN EXISTS (
            SELECT 1 FROM document_approvals da
            JOIN document_approval_steps s ON s.id_approval = da.id_approval AND s.step_order = da.current_step_order
            WHERE da.approvable_type = ? AND da.approvable_id = quotations.id AND da.status = ? AND s.id_role IN ({$placeholders})
        ) THEN 0 ELSE 1 END";

        $query = Quotation::whereIn('brand', $brands)
            ->where('status', '!=', QuotationStatus::Draft->value)
            ->whereHas('documentApprovals.steps', fn ($q) => $q->whereIn('id_role', $roleIds))
            ->with(['customer', 'branch', 'items.product', 'latestDocumentApproval.steps.role'])
            ->withDppAggregates();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('company_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('customerContact', fn ($cq) => $cq->where('full_name', 'ilike', "%{$search}%"));
            });
        }

        if ($priceTier) {
            $query->where('price_tier', $priceTier->value);
        }

        return $query
            ->orderByRaw($needsAction . ' ASC', [(new Quotation())->getMorphClass(), DocumentApprovalStatus::InProgress->value, ...$roleIds])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn (Quotation $q) => [
                ...$q->toArray(),
                'brand_label' => $q->brand->label(),
                'price_tier' => $q->price_tier?->value,
                'dpp'        => $q->aggregatedDpp(),
                ...QuotationVerifierSummary::for($q, $user),
            ]);
    }
}
