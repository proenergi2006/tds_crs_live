<?php

namespace App\Http\Controllers;

use App\Actions\Quotation\CreateQuotationAction;
use App\Actions\Quotation\GenerateQuotationPdfAction;
use App\Actions\Quotation\ReopenQuotationAction;
use App\Actions\Quotation\SubmitQuotationAction;
use App\Actions\Quotation\UpdateQuotationAction;
use App\Actions\Quotation\UpdateQuotationNonCommercialDetailsAction;
use App\Enums\QuotationBrand;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Traits\BuildsQuotationUrls;
use App\Http\Requests\Quotation\ReopenQuotationRequest as ReopenRequest;
use App\Http\Requests\Quotation\StoreQuotationRequest as StoreRequest;
use App\Http\Requests\Quotation\UpdateQuotationDetailsRequest as UpdateDetailsRequest;
use App\Http\Requests\Quotation\UpdateQuotationRequest as UpdateRequest;
use App\Http\Resources\QuotationDetailResource;
use App\Http\Resources\QuotationIndexResource;
use App\Http\Responses\ApiResponse;
use App\Models\Quotation;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    use BuildsQuotationUrls;

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

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quotation::class);

        $user = $request->user();

        $validated = $request->validate([
            'brand'               => ['nullable', Rule::enum(QuotationBrand::class)],
            'status'              => ['nullable', Rule::enum(QuotationStatus::class)],
            'customer_id'         => ['nullable', 'integer', 'min:1'],
            'per_page'            => ['nullable', 'integer', 'min:1', 'max:200'],
            'search'              => ['nullable', 'string', 'max:150'],
            'valid_today'         => ['nullable', 'boolean'],
            'without_po_customer' => ['nullable', 'boolean'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $search  = $validated['search'] ?? null;

        $query = Quotation::visibleTo($user)
            ->with(['customer', 'user', 'items.product.jenis', 'items.product.ukuran', 'latestDocumentApproval.steps.role'])
            ->withDppAggregates()
            ->withExists('poCustomers as has_po_customer');

        if (isset($validated['brand'])) {
            $query->where('brand', $validated['brand']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('customerContact', fn($cq) => $cq->where('full_name', 'ilike', "%{$search}%"));
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['customer_id'])) {
            $query->where('customer_id', (int) $validated['customer_id']);
        }

        if (! empty($validated['valid_today'])) {
            $query->validOn(now());
        }

        if ($request->boolean('without_po_customer')) {
            $query->whereDoesntHave('poCustomers');
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ApiResponse::success(
            QuotationIndexResource::collection($paginator),
            'Daftar penawaran berhasil dimuat.'
        );
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorize('view', $quotation);

        $quotation->load(self::DETAIL_RELATIONS);

        return ApiResponse::success(
            new QuotationDetailResource($quotation),
            'Detail penawaran berhasil dimuat.'
        );
    }

    public function store(StoreRequest $request, CreateQuotationAction $action): JsonResponse
    {
        $quotation = $action->execute($request->validated(), $request->resolvedBrand(), $request->user());

        return ApiResponse::success(
            new QuotationDetailResource($quotation->load(self::DETAIL_RELATIONS)),
            'Penawaran berhasil dibuat.',
            201
        );
    }

    public function update(UpdateRequest $request, Quotation $quotation, UpdateQuotationAction $action): JsonResponse
    {
        $result = $action->execute($quotation, $request->validated(), $request->user());

        $message = $result['transport_recalculated']
            ? 'Penawaran berhasil diperbarui. Harga transport dihitung ulang mengikuti tarif terbaru.'
            : 'Penawaran berhasil diperbarui.';

        return ApiResponse::success(
            new QuotationDetailResource($result['quotation']->load(self::DETAIL_RELATIONS)),
            $message,
            meta: ['transport_recalculated' => $result['transport_recalculated']]
        );
    }

    public function updateDetails(
        UpdateDetailsRequest $request,
        Quotation $quotation,
        UpdateQuotationNonCommercialDetailsAction $action
    ): JsonResponse {
        $action->execute($quotation, $request->validated(), $request->user());

        $quotation->refresh()->load(self::DETAIL_RELATIONS);

        return ApiResponse::success(
            new QuotationDetailResource($quotation),
            'Detail penawaran berhasil diperbarui.'
        );
    }

    public function destroy(Quotation $quotation, QrCodeService $qrCodeService): JsonResponse
    {
        $this->authorize('update', $quotation);

        if ($quotation->status !== QuotationStatus::Draft) {
            return ApiResponse::error(
                'Hanya penawaran draft yang bisa dihapus.',
                409
            );
        }

        try {
            $qrCodeService->deleteFiles($quotation->id, $quotation->qr_code);
        } catch (\Throwable $e) {
            report($e);
        }

        $quotation->delete();

        return ApiResponse::success(
            null,
            'Penawaran berhasil dihapus.'
        );
    }

    public function submit(Quotation $quotation, SubmitQuotationAction $action): JsonResponse
    {
        $this->authorize('update', $quotation);

        $quotation->load(['customer', 'branch', 'latestDocumentApproval.steps']);

        $message = $action->execute($quotation, $this->verificationUrl($quotation->id));

        return ApiResponse::success(
            null,
            $message
        );
    }

    public function reopen(ReopenRequest $request, Quotation $quotation, ReopenQuotationAction $action): JsonResponse
    {
        $action->execute($quotation, $request->user(), $request->validated()['catatan']);

        return ApiResponse::success(
            new QuotationDetailResource($quotation->refresh()->load(self::DETAIL_RELATIONS)),
            'Penawaran dikembalikan ke Draft.'
        );
    }

    public function preview(Request $request, Quotation $quotation, GenerateQuotationPdfAction $action): Response
    {
        $this->authorize('view', $quotation);

        $validated = $request->validate([
            'lang'         => ['nullable', Rule::in(['id', 'en'])],
            'price_format' => ['nullable', Rule::in(['summary', 'detail'])],
        ]);

        $lang = $validated['lang'] ?? 'id';
        $priceDetail = ($validated['price_format'] ?? null) === 'detail';

        return $action->execute($quotation, $lang, $priceDetail);
    }
}
