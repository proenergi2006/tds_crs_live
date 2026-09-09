<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\DecideCustomerVerificationAction;
use App\Actions\Customer\EvaluateCustomerTabCompletenessAction;
use App\Actions\Customer\GenerateCustomerDataDocumentAction;
use App\Actions\Customer\GenerateCustomerKycDocumentAction;
use App\Actions\Customer\SubmitCustomerVerificationAction;
use App\Enums\CustomerAddressType;
use App\Enums\CustomerVerificationStatus;
use App\Enums\DocumentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DecideCustomerVerificationRequest;
use App\Models\Customer;
use App\Models\CustomerLcr;
use App\Models\CustomerReview;
use App\Models\CustomerVerification;
use App\Models\Penawaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomerVerificationController extends Controller
{
    private const ROLE_ADMIN_FINANCE = 9;

    private function verificationOwnerId(CustomerVerification $customerVerification): ?int
    {
        $ownerId = Customer::where('id_customer', $customerVerification->id_customer)->value('id_user');

        return $ownerId !== null ? (int) $ownerId : null;
    }

    public function store(Request $request, Customer $customer, SubmitCustomerVerificationAction $action): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($customer->isUnderReview()) {
            return response()->json(['message' => 'Verifikasi customer ini sedang dalam review.'], 409);
        }

        $incompleteGroups = $action->incompleteGroups($customer);

        if (!empty($incompleteGroups)) {
            return response()->json([
                'message'           => 'Tidak bisa memproses verifikasi, ada data yang belum lengkap.',
                'incomplete_groups' => $incompleteGroups,
            ], 422);
        }

        $verification = $action->execute($customer, $user->id);

        return response()->json([
            'id_verification' => $verification->id_verification,
            'status'          => $verification->status->value,
            'is_scheduled'    => $verification->is_scheduled,
            'submitted_at'    => $verification->submitted_at,
            'submitted_by'    => [
                'id'   => $verification->submittedBy->id,
                'name' => $verification->submittedBy->name,
            ],
        ], 201);
    }

    public function decision(DecideCustomerVerificationRequest $request, CustomerVerification $customerVerification, DecideCustomerVerificationAction $action): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($user->cant('verification.customer') || (int) $user->primary_role_id !== self::ROLE_ADMIN_FINANCE) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($customerVerification->status !== CustomerVerificationStatus::InReview) {
            return response()->json(['message' => 'Verifikasi ini sudah tidak berstatus dalam review.'], 409);
        }

        $data = $request->validated();

        if ($data['action'] === 'approve') {
            if (!$action->lcrApproved($customerVerification->customer)) {
                return response()->json(['message' => 'LCR belum diverifikasi Logistik.'], 422);
            }

            $customerVerification = $action->approve(
                $customerVerification,
                (int) $data['approved_limit'],
                (int) $data['approved_top'],
                $data['financial_review'],
                $user->id
            );
        } else {
            $customerVerification = $action->reject($customerVerification, $data['reject_note'], $user->id);
        }

        return response()->json([
            'id_verification' => $customerVerification->id_verification,
            'status'          => $customerVerification->status->value,
            'approved_limit'  => $customerVerification->approved_limit,
            'approved_top'    => $customerVerification->approved_top,
            'reviewed_at'     => $customerVerification->reviewed_at,
            'reviewed_by'     => [
                'id'   => $customerVerification->reviewedBy->id,
                'name' => $customerVerification->reviewedBy->name,
            ],
        ]);
    }

    public function reviewIndex(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($user->cant('verification.customer') && $user->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status'   => 'nullable|in:in_review,approved,rejected',
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1',
        ]);

        $status  = CustomerVerificationStatus::from($request->query('status', CustomerVerificationStatus::InReview->value));
        $search  = trim((string) $request->query('search', ''));
        $perPage = min((int) $request->query('per_page', 25), 100);

        $rows = $this->scopedVerificationQuery($user)
            ->where('status', $status)
            ->with(['customer:id_customer,customer_code,company_name', 'submittedBy:id,name', 'reviewedBy:id,name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->whereHas('customer', function (Builder $customerQuery) use ($search) {
                    $customerQuery->where('company_name', 'ilike', "%{$search}%")
                        ->orWhere('customer_code', 'ilike', "%{$search}%");
                });
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('id_verification')
            ->paginate($perPage);

        return response()->json([
            'data' => $rows->getCollection()
                ->map(fn (CustomerVerification $verification) => $this->formatQueueRow($verification))
                ->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
                'total'        => $rows->total(),
                'per_page'     => $rows->perPage(),
            ],
        ]);
    }

    public function reviewStats(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($user->cant('verification.customer') && $user->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'in_review' => $this->scopedVerificationQuery($user)->where('status', CustomerVerificationStatus::InReview)->count(),
            'approved'  => $this->scopedVerificationQuery($user)->where('status', CustomerVerificationStatus::Approved)->count(),
            'rejected'  => $this->scopedVerificationQuery($user)->where('status', CustomerVerificationStatus::Rejected)->count(),
        ]);
    }

    public function reviewShow(Request $request, int $id, EvaluateCustomerTabCompletenessAction $evaluateTabCompleteness): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $cv = CustomerVerification::with(['customer', 'submittedBy:id,name', 'reviewedBy:id,name'])->findOrFail($id);

        $allowed = $user->can('verification.customer')
            || $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv->customer->load([
            'addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village',
            'payment', 'contacts', 'documents.documentType', 'creditRequest',
        ]);

        $headOffice = $cv->customer->addresses->firstWhere('address_type', CustomerAddressType::HeadOffice);

        $cv->customer->setRelation('province', $headOffice?->province);
        $cv->customer->setRelation('regency', $headOffice?->regency);
        $cv->customer->setRelation('district', $headOffice?->district);
        $cv->customer->setRelation('village', $headOffice?->village);
        $cv->customer->company_address = $headOffice?->address_line;
        $cv->customer->postal_code = $headOffice?->postal_code;
        $cv->customer->province_id = $headOffice?->province_id;
        $cv->customer->regency_id = $headOffice?->regency_id;
        $cv->customer->district_id = $headOffice?->district_id;
        $cv->customer->village_id = $headOffice?->village_id;

        $review = CustomerReview::where('id_customer', $cv->id_customer)->first()?->review_answers;

        $sites = CustomerLcr::where('id_customer', $cv->id_customer)->with('latestDocumentApproval')->get();

        $allApproved = $sites->isNotEmpty()
            && $sites->every(fn (CustomerLcr $site) => $site->latestDocumentApproval?->status === DocumentApprovalStatus::Approved);

        $creditRequest = $cv->customer->creditRequest;

        return response()->json([
            'id_verification'           => $cv->id_verification,
            'status'                    => $cv->status->value,
            'status_label'              => $cv->status->label(),
            'submitted_at'              => $cv->submitted_at,
            'submitted_by'              => $cv->submittedBy ? ['id' => $cv->submittedBy->id, 'name' => $cv->submittedBy->name] : null,
            'reviewed_at'               => $cv->reviewed_at,
            'reviewed_by'               => $cv->reviewedBy ? ['id' => $cv->reviewedBy->id, 'name' => $cv->reviewedBy->name] : null,
            'reject_note'               => $cv->reject_note,
            'requested_limit_snapshot'  => $cv->requested_limit_snapshot,
            'requested_top_snapshot'    => $cv->requested_top_snapshot,
            'approved_limit'            => $cv->approved_limit,
            'approved_top'              => $cv->approved_top,
            'financial_review'          => $cv->financial_review,
            'customer'                  => $cv->customer,
            'review'                    => $review,
            'lcr'                       => [
                'sites' => $sites->map(fn (CustomerLcr $site) => [
                    'id_lcr'          => $site->id_lcr,
                    'site_name'       => $site->site_name,
                    'approval_status' => $site->latestDocumentApproval?->status?->value,
                ])->values(),
                'all_approved' => $allApproved,
            ],
            'credit_request'   => $creditRequest ? [
                'requested_limit' => $creditRequest->requested_limit,
                'requested_top'   => $creditRequest->requested_top,
            ] : null,
            'tab_completeness' => $evaluateTabCompleteness->execute($cv->customer),
        ]);
    }

    public function document(Request $request, int $id, GenerateCustomerKycDocumentAction $action)
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        if (!in_array($cv->status, [CustomerVerificationStatus::InReview, CustomerVerificationStatus::Approved], true)) {
            return response()->json(['message' => 'Dokumen KYC tidak tersedia untuk verifikasi yang sudah ditolak.'], 409);
        }

        $data = $action->execute($cv);

        $pdf = \PDF::loadView('customer.kyc-document', $data)->setPaper('A4', 'portrait');

        $safeName = str_replace(['/', '\\'], '-', (string) $data['customer']->company_name);

        return $pdf->stream("KYC-{$safeName}-{$cv->id_verification}.pdf");
    }

    public function dataCustomerDocument(Request $request, int $id, GenerateCustomerDataDocumentAction $action)
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['message' => 'Fitur cetak Data Customer sedang dalam perbaikan, akan tersedia kembali.'], 503);
    }

    public function lookupForCustomer(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawarans = $customer->penawarans()
            ->withSum('items as total_volume', 'volume_order')
            ->orderByDesc('id_penawaran')
            ->get();

        $data = $penawarans->map(function (Penawaran $penawaran) {
            return [
                'id_penawaran'    => $penawaran->id_penawaran,
                'nomor_penawaran' => $penawaran->nomor_penawaran,
                'masa_berlaku'    => $penawaran->masa_berlaku,
                'sampai_dengan'   => $penawaran->sampai_dengan,
                'harga_dasar'     => $penawaran->harga_dasar,
                'oat'             => $penawaran->oat,
                'total_volume'    => $penawaran->total_volume,
            ];
        });

        return response()->json(['data' => $data]);
    }

    private function formatQueueRow(CustomerVerification $verification): array
    {
        return [
            'id_verification' => $verification->id_verification,
            'customer'        => [
                'id_customer'   => $verification->customer->id_customer,
                'company_name'  => $verification->customer->company_name,
                'customer_code' => $verification->customer->customer_code,
            ],
            'status'       => $verification->status->value,
            'status_label' => $verification->status->label(),
            'is_scheduled' => $verification->is_scheduled,
            'submitted_at' => $verification->submitted_at,
            'submitted_by' => $verification->submittedBy ? [
                'id'   => $verification->submittedBy->id,
                'name' => $verification->submittedBy->name,
            ] : null,
            'reviewed_at' => $verification->reviewed_at,
            'reviewed_by' => $verification->reviewedBy ? [
                'id'   => $verification->reviewedBy->id,
                'name' => $verification->reviewedBy->name,
            ] : null,
        ];
    }

    private function scopedVerificationQuery(User $user): Builder
    {
        $query = CustomerVerification::query();

        if ($user->cant('verification.customer')) {
            $query->whereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('id_user', $user->id));
        }

        return $query;
    }
}
