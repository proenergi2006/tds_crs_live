<?php

namespace App\Http\Controllers;

use App\Actions\Quotation\ApproveQuotationAction;
use App\Actions\Quotation\RejectQuotationAction;
use App\Actions\Quotation\ResolveQuotationQueueAction;
use App\Actions\Quotation\ResolveQuotationVerificationAction;
use App\Enums\PriceTier;
use App\Enums\QuotationBrand;
use App\Enums\QuotationVerifierRole;
use App\Http\Controllers\Traits\BuildsQuotationUrls;
use App\Http\Responses\ApiResponse;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class QuotationVerificationController extends Controller
{
    use BuildsQuotationUrls;

    public function index(Request $request, ResolveQuotationQueueAction $action): JsonResponse
    {
        $this->authorize('viewVerificationQueue', Quotation::class);

        $user = $request->user();
        $verifiable = QuotationBrand::verifiableBy($user);

        $validated = $request->validate([
            'price_tier' => ['nullable', Rule::enum(PriceTier::class)],
            'brand'      => ['nullable', Rule::enum(QuotationBrand::class)],
            'search'     => ['nullable', 'string', 'max:150'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $priceTier = isset($validated['price_tier']) ? PriceTier::from($validated['price_tier']) : null;
        $brands = isset($validated['brand'])
            ? array_values(array_filter($verifiable, fn (QuotationBrand $brand) => $brand->value === $validated['brand']))
            : $verifiable;

        $data = $action->execute(
            $brands,
            $this->queueRoleIds($user),
            $validated['search'] ?? null,
            (int) ($validated['per_page'] ?? 10),
            $user,
            $priceTier
        );

        return ApiResponse::success(
            JsonResource::collection($data),
            'Antrean verifikasi penawaran berhasil dimuat.'
        );
    }

    public function approve(Request $request, Quotation $quotation, ApproveQuotationAction $action): JsonResponse
    {
        $quotation->load('latestDocumentApproval.steps');

        if (! $quotation->isAwaitingVerification()) {
            return ApiResponse::error(
                'Penawaran tidak sedang menunggu verifikasi.',
                409
            );
        }

        $this->authorize('verify', $quotation);
        $validated = $request->validate(['catatan' => 'nullable|string']);

        $message = $action->execute(
            $quotation,
            $request->user(),
            $validated['catatan'] ?? null,
            $this->detailUrl($quotation->id),
            $this->verificationUrl($quotation->id)
        );

        return ApiResponse::success(
            null,
            $message
        );
    }

    public function reject(Request $request, Quotation $quotation, RejectQuotationAction $action): JsonResponse
    {
        $quotation->load('latestDocumentApproval.steps');

        if (! $quotation->isAwaitingVerification()) {
            return ApiResponse::error(
                'Penawaran tidak sedang menunggu verifikasi.',
                409
            );
        }

        $this->authorize('verify', $quotation);
        $validated = $request->validate(['catatan' => 'required|string']);

        $action->execute(
            $quotation,
            $request->user(),
            $validated['catatan'],
            $this->detailUrl($quotation->id)
        );

        return ApiResponse::success(
            null,
            'Status penawaran Ditolak'
        );
    }

    public function showPublic(string $token, ResolveQuotationVerificationAction $action): JsonResponse
    {
        $result = $action->execute($token);

        if ($result === null) {
            abort(404);
        }

        return ApiResponse::success(
            $result,
            'Status verifikasi penawaran berhasil dimuat.'
        );
    }

    private function queueRoleIds(User $user): array
    {
        $all = array_column(QuotationVerifierRole::cases(), 'value');

        if ($user->hasRole('Administrator')) {
            return $all;
        }

        return array_values(array_intersect($user->roles->pluck('id')->all(), $all));
    }
}
