<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\UpsertCustomerArAgingRequest;
use App\Models\Customer;
use App\Models\CustomerAdminArnya;
use Illuminate\Http\Request;

class CustomerArAgingController extends Controller
{
    private const ROLE_ADMIN_FINANCE = 9;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! $this->canManageArAging($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $search  = trim((string) $request->query('search', ''));
        $perPage = min((int) $request->query('per_page', 25), 100);

        $query = Customer::query()->with('adminArnya.updatedBy');

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(fn ($q) => $q
                ->where('company_name', 'ILIKE', $like)
                ->orWhere('customer_code', 'ILIKE', $like));
        }

        $paginated = $query->orderBy('company_name')->paginate($perPage);
        $paginated->getCollection()->transform(fn (Customer $customer) => $this->formatAging($customer));

        return response()->json($paginated);
    }

    public function show(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (! $this->canManageArAging($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer->load('adminArnya.updatedBy');

        return response()->json($this->formatAging($customer));
    }

    public function upsert(UpsertCustomerArAgingRequest $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (! $this->canManageArAging($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        CustomerAdminArnya::updateOrCreate(
            ['id_customer' => $customer->id_customer],
            [...$data, 'updated_by' => $request->user()->id]
        );

        $customer->load('adminArnya.updatedBy');

        return response()->json($this->formatAging($customer));
    }

    private function canManageArAging(Request $request): bool
    {
        $user = $request->user();

        return $user->can('sales-confirmation.manage')
            && (int) $user->primary_role_id === self::ROLE_ADMIN_FINANCE;
    }

    private function formatAging(Customer $customer): array
    {
        $arnya = $customer->adminArnya;

        return [
            'id_customer'         => $customer->id_customer,
            'customer_code'       => $customer->customer_code,
            'company_name'        => $customer->company_name,
            'outstanding_current' => (float) ($arnya->outstanding_current ?? 0),
            'overdue_1_30'        => (float) ($arnya->overdue_1_30 ?? 0),
            'overdue_31_60'       => (float) ($arnya->overdue_31_60 ?? 0),
            'overdue_61_90'       => (float) ($arnya->overdue_61_90 ?? 0),
            'overdue_90_plus'     => (float) ($arnya->overdue_90_plus ?? 0),
            'total_ar'            => (float) ($arnya?->total_ar ?? 0),
            'updated_by'          => $arnya?->updatedBy
                ? ['id' => $arnya->updatedBy->id, 'name' => $arnya->updatedBy->name]
                : null,
            'updated_at'         => optional($arnya?->updated_at)->toISOString(),
        ];
    }
}
