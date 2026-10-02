<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Concerns\GuardsCustomerEditLock;
use App\Http\Requests\Customer\UpsertCustomerCreditRequestRequest;
use App\Models\Customer;
use App\Models\CustomerCreditRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;

class CustomerCreditRequestController extends Controller
{
    use GuardsCustomerEditLock;

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer->loadMissing('creditRequest.createdBy:id,name', 'creditRequest.updatedBy:id,name');

        return response()->json($this->formatCreditRequest($customer->creditRequest));
    }

    public function update(UpsertCustomerCreditRequestRequest $request, Customer $customer): JsonResponse
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($response = $this->blockIfCustomerEditLocked($customer)) {
            return $response;
        }

        $data = $request->validated();
        $existing = $customer->creditRequest;

        $attributes = [
            'requested_limit'  => $data['requested_limit'] ?? null,
            'requested_top'    => $data['requested_top'] ?? null,
            'requested_qty'    => $data['requested_qty'] ?? null,
            'product_category' => $data['product_category'] ?? null,
            'financial_review' => filled($data['financial_review'] ?? null) ? Purifier::clean($data['financial_review'], 'ckeditor') : null,
            'updated_by'       => $user->id,
        ];

        if (!$existing) {
            $attributes['created_by'] = $user->id;
        }

        $creditRequest = CustomerCreditRequest::updateOrCreate(
            ['id_customer' => $customer->id_customer],
            $attributes
        );

        $creditRequest->load('createdBy:id,name', 'updatedBy:id,name');

        return response()->json($this->formatCreditRequest($creditRequest));
    }

    private function formatCreditRequest(?CustomerCreditRequest $creditRequest): ?array
    {
        if (!$creditRequest) {
            return null;
        }

        return [
            'id_request'              => $creditRequest->id_request,
            'requested_limit'         => $creditRequest->requested_limit,
            'requested_top'           => $creditRequest->requested_top,
            'requested_qty'           => $creditRequest->requested_qty,
            'product_category'        => $creditRequest->product_category?->value,
            'product_category_label'  => $creditRequest->product_category?->label(),
            'unit'                    => $creditRequest->product_category?->unit(),
            'financial_review'        => $creditRequest->financial_review,
            'created_by'              => $creditRequest->createdBy ? [
                'id'   => $creditRequest->createdBy->id,
                'name' => $creditRequest->createdBy->name,
            ] : null,
            'updated_by'              => $creditRequest->updatedBy ? [
                'id'   => $creditRequest->updatedBy->id,
                'name' => $creditRequest->updatedBy->name,
            ] : null,
            'created_at'              => optional($creditRequest->created_at)->toISOString(),
            'updated_at'              => optional($creditRequest->updated_at)->toISOString(),
        ];
    }
}
