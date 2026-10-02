<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Actions\MasterLogistik\CreateWithDocumentsAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MasterLogistik\Concerns\DeletesLogisticDocumentFiles;
use App\Http\Requests\MasterLogistik\StoreVesselRequest;
use App\Http\Requests\MasterLogistik\UpdateVesselRequest;
use App\Http\Resources\MasterLogistik\VesselResource;
use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VesselController extends Controller
{
    use DeletesLogisticDocumentFiles;

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Vessel::with('transporter:id,company_name')->withCount('logisticDocuments');

        if ($search = $request->query('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $query->latest();

        if ($request->boolean('as_list')) {
            return VesselResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return VesselResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, $id): JsonResponse|VesselResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vessel = Vessel::with('transporter:id,company_name')->withCount('logisticDocuments')->findOrFail($id);

        return new VesselResource($vessel);
    }

    public function store(StoreVesselRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $entityData = $validated['data_vessel'] + ['created_by' => $request->user()->id];
        $documents = $validated['data_dokumen'] ?? [];

        $vessel = (new CreateWithDocumentsAction)->execute(Vessel::class, $entityData, $documents);
        $vessel->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return (new VesselResource($vessel))->response()->setStatusCode(201);
    }

    public function update(UpdateVesselRequest $request, $id): JsonResponse|VesselResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vessel = Vessel::findOrFail($id);
        $vessel->update($request->validated() + ['updated_by' => $request->user()->id]);
        $vessel->refresh()->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return new VesselResource($vessel);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vessel = Vessel::findOrFail($id);
        $this->deleteWithLogisticDocumentFiles($vessel);

        return response()->json(null, 204);
    }
}
