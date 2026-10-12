<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Enums\TransportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterLogistik\StoreTransportTariffRequest;
use App\Http\Requests\MasterLogistik\UpdateTransportTariffRequest;
use App\Http\Resources\MasterLogistik\TransportTariffResource;
use App\Models\TransportTariff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TransportTariffController extends Controller
{
    private const EAGER_RELATIONS = ['transporter:id,company_name', 'transportArea:id,name', 'volume:id,volume'];

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if (! $request->user()->canAny(['logistik.master.manage', 'logistik.transport-tariff.view'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'transport_type' => ['nullable', Rule::enum(TransportType::class)],
        ]);

        $query = TransportTariff::with(self::EAGER_RELATIONS);

        if ($request->filled('transport_type')) {
            $query->where('transport_type', $request->query('transport_type'));
        }

        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->query('transporter_id'));
        }

        if ($request->filled('transport_area_id')) {
            $query->where('transport_area_id', $request->query('transport_area_id'));
        }

        $query->latest();

        if ($request->boolean('as_list')) {
            return TransportTariffResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return TransportTariffResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, TransportTariff $transportTariff): JsonResponse|TransportTariffResource
    {
        if (! $request->user()->canAny(['logistik.master.manage', 'logistik.transport-tariff.view'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transportTariff->load(self::EAGER_RELATIONS);

        return new TransportTariffResource($transportTariff);
    }

    public function store(StoreTransportTariffRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        if ($data['is_active'] && $this->hasActiveDuplicate($data, null)) {
            return $this->duplicateTariffResponse();
        }

        $tariff = TransportTariff::create($data + ['created_by' => $request->user()->id])
            ->refresh()
            ->load(self::EAGER_RELATIONS);

        return (new TransportTariffResource($tariff))->response()->setStatusCode(201);
    }

    public function update(UpdateTransportTariffRequest $request, TransportTariff $transportTariff): JsonResponse|TransportTariffResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? $transportTariff->is_active;

        if ($data['is_active'] && $this->hasActiveDuplicate($data, $transportTariff->id)) {
            return $this->duplicateTariffResponse();
        }

        $transportTariff->update($data + ['updated_by' => $request->user()->id]);
        $transportTariff->refresh()->load(self::EAGER_RELATIONS);

        return new TransportTariffResource($transportTariff);
    }

    public function destroy(Request $request, TransportTariff $transportTariff): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transportTariff->delete();

        return response()->json(null, 204);
    }

    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->cant('logistik.master.manage') && $user->cant('logistik.master.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'transporter_id' => 'required|integer',
            'transport_type' => ['required', Rule::enum(TransportType::class)],
            'transport_area_id' => 'required|integer',
            'volume_id' => 'required|integer',
        ]);

        $tariff = TransportTariff::where('transporter_id', $data['transporter_id'])
            ->where('transport_type', $data['transport_type'])
            ->where('transport_area_id', $data['transport_area_id'])
            ->where('volume_id', $data['volume_id'])
            ->where('is_active', true)
            ->first();

        if (! $tariff) {
            return response()->json(['data' => null]);
        }

        $price = $tariff->sellingPrice();

        return response()->json([
            'data' => [
                'id' => $tariff->id,
                'transport_type' => $tariff->transport_type->value,
                'transporter_id' => $tariff->transporter_id,
                'transport_area_id' => $tariff->transport_area_id,
                'volume_id' => $tariff->volume_id,
                'available' => $price !== null,
                'price' => $price,
            ],
        ]);
    }

    private function hasActiveDuplicate(array $data, ?int $ignoreId): bool
    {
        return TransportTariff::where('transporter_id', $data['transporter_id'])
            ->where('transport_type', $data['transport_type'])
            ->where('transport_area_id', $data['transport_area_id'])
            ->where('volume_id', $data['volume_id'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function duplicateTariffResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Sudah ada tarif aktif untuk kombinasi Transporter, Transport Type, Transport Area, dan Volume ini.',
            'errors' => ['is_active' => ['Sudah ada tarif aktif untuk kombinasi ini.']],
        ], 422);
    }
}
