<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterLogistik\StoreTransportAreaRequest;
use App\Http\Requests\MasterLogistik\UpdateTransportAreaRequest;
use App\Http\Resources\MasterLogistik\TransportAreaResource;
use App\Models\CustomerLcr;
use App\Models\TransportArea;
use App\Models\TransportTariff;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransportAreaController extends Controller
{
    private const EAGER_RELATIONS = ['provinsi', 'kabupaten', 'province', 'regency'];

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user->cant('logistik.master.manage') && $user->cant('logistik.master.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = TransportArea::with(self::EAGER_RELATIONS);

        if ($search = $request->query('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $query->orderBy('name');

        if ($request->boolean('as_list')) {
            return TransportAreaResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return TransportAreaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, $id): JsonResponse|TransportAreaResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transportArea = TransportArea::with(self::EAGER_RELATIONS)->findOrFail($id);

        return new TransportAreaResource($transportArea);
    }

    public function store(StoreTransportAreaRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated() + ['created_by' => $request->user()->id];

        $transportArea = TransportArea::create($data)->refresh()->load(self::EAGER_RELATIONS);

        return (new TransportAreaResource($transportArea))->response()->setStatusCode(201);
    }

    public function update(UpdateTransportAreaRequest $request, $id): JsonResponse|TransportAreaResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transportArea = TransportArea::findOrFail($id);
        $transportArea->update($request->validated() + ['updated_by' => $request->user()->id]);
        $transportArea->refresh()->load(self::EAGER_RELATIONS);

        return new TransportAreaResource($transportArea);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transportArea = TransportArea::findOrFail($id);

        $tariffCount = TransportTariff::where('transport_area_id', $transportArea->id)->count();
        $lcrCount = CustomerLcr::where('id_wil_oa', $transportArea->id)->count();

        if ($tariffCount > 0 || $lcrCount > 0) {
            return response()->json([
                'message' => 'Transport Area masih dipakai oleh tarif angkut atau LCR customer, tidak bisa dihapus.',
                'dependents' => [
                    'transport_tariffs' => $tariffCount,
                    'customer_lcr' => $lcrCount,
                ],
            ], 409);
        }

        try {
            $transportArea->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                return response()->json(['message' => 'Transport Area masih dipakai data lain, silakan coba lagi.'], 409);
            }

            throw $e;
        }

        return response()->json(null, 204);
    }
}
