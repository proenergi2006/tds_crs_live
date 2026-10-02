<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Actions\MasterLogistik\CreateWithDocumentsAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MasterLogistik\Concerns\DeletesLogisticDocumentFiles;
use App\Http\Requests\MasterLogistik\StoreTruckRequest;
use App\Http\Requests\MasterLogistik\UpdateTruckRequest;
use App\Http\Resources\MasterLogistik\TruckResource;
use App\Models\Truck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TruckController extends Controller
{
    use DeletesLogisticDocumentFiles;

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Truck::with('transporter:id,company_name')->withCount('logisticDocuments');

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('license_plate', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%"));
        }

        $query->latest();

        if ($request->boolean('as_list')) {
            return TruckResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return TruckResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, $id): JsonResponse|TruckResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $truck = Truck::with('transporter:id,company_name')->withCount('logisticDocuments')->findOrFail($id);

        return new TruckResource($truck);
    }

    public function store(StoreTruckRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $entityData = $validated['data_truck'] + ['created_by' => $request->user()->id];
        $documents = $validated['data_dokumen'] ?? [];

        $truck = (new CreateWithDocumentsAction)->execute(Truck::class, $entityData, $documents);
        $truck->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return (new TruckResource($truck))->response()->setStatusCode(201);
    }

    public function update(UpdateTruckRequest $request, $id): JsonResponse|TruckResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $truck = Truck::findOrFail($id);
        $truck->update($request->validated() + ['updated_by' => $request->user()->id]);
        $truck->refresh()->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return new TruckResource($truck);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $truck = Truck::findOrFail($id);
        $this->deleteWithLogisticDocumentFiles($truck);

        return response()->json(null, 204);
    }
}
