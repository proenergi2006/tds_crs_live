<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerCreditItemRequest;
use App\Http\Requests\Customer\UpdateCustomerCreditItemRequest;
use App\Models\Customer;
use App\Models\CustomerCreditItem;
use App\Models\CustomerCreditSubmission;
use Illuminate\Http\Request;

class CustomerCreditItemController extends Controller
{
    public function store(StoreCustomerCreditItemRequest $request, Customer $customer, CustomerCreditSubmission $submission)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($submission->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Pengajuan kredit tidak ditemukan untuk customer ini.'], 404);
        }

        $data = $request->validated();

        $item = $submission->items()->create($data);
        $item->load('produk');

        return response()->json($this->formatItem($item), 201);
    }

    public function update(UpdateCustomerCreditItemRequest $request, Customer $customer, CustomerCreditSubmission $submission, CustomerCreditItem $item)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($submission->id_customer !== $customer->id_customer || $item->id_submission !== $submission->id) {
            return response()->json(['message' => 'Item pengajuan kredit tidak ditemukan.'], 404);
        }

        $data = $request->validated();

        $item->update($data);

        return response()->json($this->formatItem($item->fresh('produk')));
    }

    public function destroy(Request $request, Customer $customer, CustomerCreditSubmission $submission, CustomerCreditItem $item)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($submission->id_customer !== $customer->id_customer || $item->id_submission !== $submission->id) {
            return response()->json(['message' => 'Item pengajuan kredit tidak ditemukan.'], 404);
        }

        $item->delete();

        return response()->json(null, 204);
    }

    private function formatItem(CustomerCreditItem $item): array
    {
        return [
            'id'                     => $item->id,
            'id_submission'          => $item->id_submission,
            'id_produk'              => $item->id_produk,
            'produk'                 => $item->produk ? [
                'id_produk'   => $item->produk->id_produk,
                'nama_produk' => $item->produk->nama_produk,
            ] : null,
            'volume'                 => $item->volume,
            'unit'                   => $item->unit,
            'existing_limit'         => $item->existing_limit,
            'actual_payment'         => $item->actual_payment,
            'guarantee'              => $item->guarantee,
            'credit_limit_request'   => $item->credit_limit_request,
            'credit_limit_approval'  => $item->credit_limit_approval,
            'top_request'            => $item->top_request,
            'top_approval'           => $item->top_approval,
            'notes'                  => $item->notes,
            'created_at' => optional($item->created_at)->toISOString(),
            'updated_at' => optional($item->updated_at)->toISOString(),
        ];
    }
}
