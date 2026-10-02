<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Actions\MasterLogistik\CreateWithDocumentsAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MasterLogistik\Concerns\DeletesLogisticDocumentFiles;
use App\Http\Requests\MasterLogistik\StoreTransporterRequest;
use App\Http\Requests\MasterLogistik\UpdateTransporterRequest;
use App\Http\Resources\MasterLogistik\TransporterResource;
use App\Models\Transporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransporterController extends Controller
{
    use DeletesLogisticDocumentFiles;

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user->cant('logistik.master.manage') && $user->cant('logistik.master.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Transporter::with('cabang');

        if ($user->can('logistik.master.manage')) {
            $query->withCount(['personnels', 'vessels', 'trucks']);
        }

        if ($search = $request->query('search')) {
            $query->where('company_name', 'ilike', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('company_name');

        if ($request->boolean('as_list')) {
            return TransporterResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return TransporterResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, $id): JsonResponse|TransporterResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transporter = Transporter::with('cabang')
            ->withCount(['personnels', 'vessels', 'trucks'])
            ->findOrFail($id);

        return new TransporterResource($transporter);
    }

    public function store(StoreTransporterRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $entityData = $validated['data_transporter'] + ['created_by' => $request->user()->id];
        $documents = $validated['data_dokumen'] ?? [];

        $transporter = (new CreateWithDocumentsAction)->execute(Transporter::class, $entityData, $documents);
        $transporter->load('cabang')->loadCount(['personnels', 'vessels', 'trucks']);

        return (new TransporterResource($transporter))->response()->setStatusCode(201);
    }

    public function update(UpdateTransporterRequest $request, $id): JsonResponse|TransporterResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transporter = Transporter::findOrFail($id);
        $transporter->update($request->validated() + ['updated_by' => $request->user()->id]);
        $transporter->refresh()->load('cabang')->loadCount(['personnels', 'vessels', 'trucks']);

        return new TransporterResource($transporter);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transporter = Transporter::withCount(['personnels', 'vessels', 'trucks'])->findOrFail($id);

        if ($transporter->personnels_count > 0 || $transporter->vessels_count > 0 || $transporter->trucks_count > 0) {
            return response()->json([
                'message' => 'Transporter masih memiliki data terkait yang harus dihapus/dipindahkan terlebih dahulu.',
                'dependents' => [
                    'personnels' => (int) $transporter->personnels_count,
                    'vessels' => (int) $transporter->vessels_count,
                    'trucks' => (int) $transporter->trucks_count,
                ],
            ], 409);
        }

        try {
            $this->deleteWithLogisticDocumentFiles($transporter);
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                return response()->json(['message' => 'Transporter masih memiliki data terkait, silakan coba lagi.'], 409);
            }

            throw $e;
        }

        return response()->json(null, 204);
    }
}
