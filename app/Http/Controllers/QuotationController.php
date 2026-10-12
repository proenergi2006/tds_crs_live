<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Http\Requests\Quotation\UpdateQuotationRequest;
use App\Http\Resources\QuotationIndexResource;
use App\Http\Resources\QuotationDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use App\Services\QrCodeService;
use App\Actions\Quotation\CreateQuotationAction;
use App\Actions\Quotation\UpdateQuotationAction;
use App\Actions\Quotation\SubmitQuotationAction;
use App\Actions\Quotation\UpdateQuotationNonCommercialDetailsAction;
use App\Http\Requests\Quotation\UpdateQuotationDetailsRequest;
use App\Actions\Quotation\ApproveQuotationAction;
use App\Actions\Quotation\RejectQuotationAction;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Models\DocumentApprovalStep;
use App\Models\User;
use App\Actions\Quotation\ResolveQuotationQueueAction;
use App\Actions\Quotation\GenerateQuotationPdfAction;

class QuotationController extends Controller
{
    private const DETAIL_RELATIONS = [
        'customer',
        'customer.headOfficeAddress',
        'customerContact',
        'branch',
        'items.product.jenis',
        'items.product.ukuran.satuan',
        'items.productPrice',
        'items.sourceBranch',
        'transports.transportTariff.volume',
        'transports.transportTariff.transporter',
        'transports.transportTariff.transportArea.provinsi',
        'transports.transportTariff.transportArea.kabupaten',
        'transports.transportTariff.transportArea.province',
        'transports.transportTariff.transportArea.regency',
        'documentApprovals.steps.templateStep',
        'documentApprovals.steps.role',
        'documentApprovals.steps.actor',
        'latestDocumentApproval.steps.role',
    ];

    private function resolveQuotation(string $brand, int $id, array $with = []): Quotation
    {
        return Quotation::where('brand', $brand)->with($with)->findOrFail($id);
    }

    private function authorizeActiveVerifier(Request $request, Quotation $quotation): DocumentApprovalStep
    {
        $step = $quotation->activeVerificationStep();

        if ($quotation->status !== QuotationStatus::WaitingVerification || ! $step) {
            throw new HttpResponseException(response()->json(['message' => 'Penawaran tidak sedang menunggu verifikasi.'], 409));
        }

        if (QuotationVerifierRole::tryFrom((int) $step->id_role)?->isHeldBy($request->user()) !== true) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }

        return $step;
    }

    private function verificationResponse(array $result)
    {
        $payload = ['message' => $result['message']];
        if (isset($result['error'])) {
            $payload['error'] = $result['error'];
        }

        return response()->json($payload, $result['status'] ?? 200);
    }

    public function index(Request $request, string $brand)
    {
        $user = $request->user();
        $permissionAny = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        $permissionOwn = $brand === 'proenergi' ? 'penawaran.proenergi.viewOwn' : 'penawaran.viewOwn';

        if ($user->cant($permissionAny) && $user->cant($permissionOwn)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Quotation::where('brand', $brand)
            ->with(['customer', 'user', 'items.product.jenis', 'items.product.ukuran', 'latestDocumentApproval.steps.role'])
            ->withDppAggregates();

        if ($user->cant($permissionAny)) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('customerContact', fn($cq) => $cq->where('full_name', 'ilike', "%{$search}%"));
            });
        }

        $status = $request->query('status');
        if ($status) {
            $query->where('status', $status);
        }

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', (int) $customerId);
        }

        if ($request->boolean('valid_today')) {
            $query->validOn(now());
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $paginator->through(fn ($quotation) => (new QuotationIndexResource($quotation))->resolve());

        return response()->json($paginator);
    }

    public function show(Request $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id, self::DETAIL_RELATIONS);

        $user = $request->user();
        $permissionAny = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        $permissionOwn = $brand === 'proenergi' ? 'penawaran.proenergi.viewOwn' : 'penawaran.viewOwn';
        $allowed = $user->can($permissionAny)
            || ($user->can($permissionOwn) && (int) $quotation->user_id === (int) $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json((new QuotationDetailResource($quotation))->resolve());
    }

    public function store(StoreQuotationRequest $request, string $brand)
    {
        try {
            $quotation = app(CreateQuotationAction::class)->execute($request->validated(), $brand, $request->user());

            return response()->json(
                (new QuotationDetailResource($quotation->load(self::DETAIL_RELATIONS)))->resolve(),
                201
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal menyimpan penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateQuotationRequest $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id);

        if ($quotation->status !== QuotationStatus::Draft) {
            return response()->json(['message' => 'Hanya penawaran draft yang bisa diubah.'], 409);
        }

        try {
            $result = app(UpdateQuotationAction::class)->execute($quotation, $request->validated(), $request->user());

            return response()->json([
                ...(new QuotationDetailResource($result['quotation']->load(self::DETAIL_RELATIONS)))->resolve(),
                'transport_recalculated' => $result['transport_recalculated'],
            ]);
        } catch (ValidationException|HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal update penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateDetails(UpdateQuotationDetailsRequest $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id);

        if (! $quotation->isNonCommercialEditableStatus()) {
            return response()->json(['message' => 'Detail non-komersial hanya bisa diubah pada penawaran yang sedang diverifikasi atau sudah disetujui.'], 409);
        }

        app(UpdateQuotationNonCommercialDetailsAction::class)->execute($quotation, $request->validated(), $request->user());

        $quotation = $this->resolveQuotation($brand, (int) $id, self::DETAIL_RELATIONS);

        return response()->json((new QuotationDetailResource($quotation))->resolve());
    }

    public function destroy(Request $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id);

        $user = $request->user();
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.manage' : 'penawaran.manage';
        $allowed = $user->can($permission) && (int) $quotation->user_id === (int) $user->id;

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quotationId = (int) $quotation->id;
        $qrUrl = $quotation->qr_code;

        try {
            (new QrCodeService())->deleteFiles($quotationId, $qrUrl);
        } catch (\Throwable $e) {
            report($e);
        }

        $quotation->delete();

        return response()->json(null, 204);
    }

    public function ajukan($id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id, ['customer', 'branch', 'latestDocumentApproval.steps']);

        $result = app(SubmitQuotationAction::class)->execute(
            $quotation,
            fn (QuotationVerifierRole $role) => $this->verificationUrl($brand, $quotation->id, $role->urlSegment())
        );

        $response = ['message' => $result['message']];
        if (array_key_exists('error', $result)) {
            $response['error'] = $result['error'];
        }

        return response()->json($response, $result['status'] ?? 200);
    }

    public function bmVerificationIndex(Request $request, string $brand)
    {
        return $this->verificationQueue($request, $brand, QuotationVerifierRole::branchManagerRoles());
    }

    public function omVerificationIndex(Request $request, string $brand)
    {
        return $this->verificationQueue($request, $brand, QuotationVerifierRole::operationManagerRoles());
    }

    public function ceoVerificationIndex(Request $request, string $brand)
    {
        if ($request->user()->cant(QuotationVerifierRole::Ceo->permission())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->runQueue($request, $brand, $this->queueRoleIds($request->user(), [QuotationVerifierRole::Ceo->value]));
    }

    public function approve(Request $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id, ['latestDocumentApproval.steps']);
        $this->authorizeActiveVerifier($request, $quotation);
        $request->validate(['catatan' => 'nullable|string']);

        $result = (new ApproveQuotationAction())->execute(
            $quotation,
            $request->user(),
            $request->catatan,
            $this->detailUrl($brand, $quotation->id),
            fn (QuotationVerifierRole $role) => $this->verificationUrl($brand, $quotation->id, $role->urlSegment())
        );

        return $this->verificationResponse($result);
    }

    public function reject(Request $request, $id, string $brand)
    {
        $quotation = $this->resolveQuotation($brand, (int) $id, ['latestDocumentApproval.steps']);
        $this->authorizeActiveVerifier($request, $quotation);
        $request->validate(['catatan' => 'required|string']);

        $result = (new RejectQuotationAction())->execute(
            $quotation,
            $request->user(),
            $request->catatan,
            $this->detailUrl($brand, $quotation->id)
        );

        return $this->verificationResponse($result);
    }
    public function previewPdfMultiLang(Request $request, $id, string $brand)
    {
        $lang = strtolower($request->query('lang', 'id'));
        $priceDetail = $request->query('price_format') === 'detail';

        $viewPrefix = $brand === 'proenergi' ? 'quotation.pdf_proenergi' : 'quotation.pdf';
        $logoLeftPath = $brand === 'proenergi'
            ? public_path('images/logo-proenergi.png')
            : public_path('images/logo-new.png');

        return (new GenerateQuotationPdfAction())->execute(
            $brand,
            (int) $id,
            $lang,
            $priceDetail,
            $viewPrefix,
            $logoLeftPath
        );
    }

    private function verificationQueue(Request $request, string $brand, array $family)
    {
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';

        if ($request->user()->cant($permission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->runQueue($request, $brand, $this->queueRoleIds($request->user(), $family));
    }

    private function runQueue(Request $request, string $brand, array $roleIds)
    {
        $data = (new ResolveQuotationQueueAction())->execute(
            $brand,
            $roleIds,
            $request->query('search'),
            (int) $request->query('per_page', 10),
            $request->user()
        );

        return response()->json($data);
    }

    private function queueRoleIds(User $user, array $family): array
    {
        if ($user->hasRole('Administrator')) {
            return $family;
        }

        return array_values(array_intersect($user->roles->pluck('id')->all(), $family));
    }

    private function detailUrl(string $brand, int $quotationId): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/{$quotationId}");
    }

    private function verificationUrl(string $brand, int $quotationId, string $role): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/verifikasi/{$role}/{$quotationId}");
    }
}
