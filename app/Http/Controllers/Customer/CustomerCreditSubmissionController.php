<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerCreditSubmissionRequest;
use App\Http\Requests\Customer\UpdateCustomerCreditSubmissionRequest;
use App\Models\Customer;
use App\Models\CustomerCreditItem;
use App\Models\CustomerCreditSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCreditSubmissionController extends Controller
{
    private const RELATIONS = ['items.produk', 'createdBy', 'updatedBy', 'latestDocumentApproval.steps'];

    public function index(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $submissions = $customer->creditSubmissions()
            ->with(self::RELATIONS)
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $submissions->map(fn (CustomerCreditSubmission $submission) => $this->formatSubmission($submission))->values()
        );
    }

    public function show(Request $request, Customer $customer, CustomerCreditSubmission $submission)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($submission->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Pengajuan kredit tidak ditemukan untuk customer ini.'], 404);
        }

        $submission->load(self::RELATIONS);

        return response()->json($this->formatSubmission($submission));
    }

    public function store(StoreCustomerCreditSubmissionRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $submission = DB::transaction(function () use ($data, $customer, $user) {
            $submission = CustomerCreditSubmission::create([
                'id_customer'      => $customer->id_customer,
                'submission_type'  => $data['submission_type'],
                'top_payment'      => $data['top_payment'] ?? null,
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $submission->items()->create($this->itemPayload($item));
            }

            return $submission;
        });

        $submission->load(self::RELATIONS);

        return response()->json($this->formatSubmission($submission), 201);
    }

    public function update(UpdateCustomerCreditSubmissionRequest $request, Customer $customer, CustomerCreditSubmission $submission)
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

        $submission->update([
            'submission_type' => $data['submission_type'],
            'top_payment'      => $data['top_payment'] ?? null,
            'updated_by'       => $user->id,
        ]);

        return response()->json($this->formatSubmission($submission->fresh(self::RELATIONS)));
    }

    public function destroy(Request $request, Customer $customer, CustomerCreditSubmission $submission)
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

        $submission->delete();

        return response()->json(null, 204);
    }

    private function itemPayload(array $item): array
    {
        return [
            'id_produk'              => $item['id_produk'],
            'volume'                 => $item['volume'] ?? null,
            'unit'                   => $item['unit'] ?? null,
            'existing_limit'         => $item['existing_limit'] ?? null,
            'actual_payment'         => $item['actual_payment'] ?? null,
            'guarantee'              => $item['guarantee'] ?? null,
            'credit_limit_request'   => $item['credit_limit_request'] ?? null,
            'credit_limit_approval'  => $item['credit_limit_approval'] ?? null,
            'top_request'            => $item['top_request'] ?? null,
            'top_approval'           => $item['top_approval'] ?? null,
            'notes'                  => $item['notes'] ?? null,
        ];
    }

    private function formatSubmission(CustomerCreditSubmission $submission): array
    {
        $cycle = $submission->latestDocumentApproval;

        return [
            'id'               => $submission->id,
            'id_customer'      => $submission->id_customer,
            'submission_type'  => $submission->submission_type->value,
            'submission_type_label' => $submission->submission_type->label(),
            'top_payment'      => $submission->top_payment,
            'items'            => $submission->items->map(fn (CustomerCreditItem $item) => $this->formatItem($item))->values(),
            'approval'         => $cycle ? [
                'status'              => $cycle->status->value,
                'status_label'        => $cycle->status->label(),
                'current_step_order'  => $cycle->current_step_order,
            ] : null,
            'created_by' => $submission->createdBy ? [
                'id'   => $submission->createdBy->id,
                'name' => $submission->createdBy->name,
            ] : null,
            'updated_by' => $submission->updatedBy ? [
                'id'   => $submission->updatedBy->id,
                'name' => $submission->updatedBy->name,
            ] : null,
            'created_at' => optional($submission->created_at)->toISOString(),
            'updated_at' => optional($submission->updated_at)->toISOString(),
        ];
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
