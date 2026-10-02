<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Actions\MasterLogistik\CreateWithDocumentsAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MasterLogistik\Concerns\DeletesLogisticDocumentFiles;
use App\Http\Requests\MasterLogistik\StorePersonnelRequest;
use App\Http\Resources\MasterLogistik\PersonnelResource;
use App\Models\Personnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class PersonnelController extends Controller
{
    use DeletesLogisticDocumentFiles;

    private const RULES = [
        'transporter_id' => 'required|exists:transporters,id',
        'name' => 'required|string|max:255',
        'photo' => 'nullable|image|max:2048',
        'is_active' => 'boolean',
    ];

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Personnel::with('transporter:id,company_name')->withCount('logisticDocuments');

        if ($search = $request->query('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $query->latest();

        if ($request->boolean('as_list')) {
            return PersonnelResource::collection($query->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return PersonnelResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, $id): JsonResponse|PersonnelResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $personnel = Personnel::with('transporter:id,company_name')->withCount('logisticDocuments')->findOrFail($id);

        return new PersonnelResource($personnel);
    }

    public function store(StorePersonnelRequest $request): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $entityData = $validated['data_personnel'];
        $documents = $validated['data_dokumen'] ?? [];

        if ($request->hasFile('data_personnel.photo')) {
            $entityData['photo'] = $request->file('data_personnel.photo')->store('personnels/photos', 'public');
        } else {
            unset($entityData['photo']);
        }

        $entityData['created_by'] = $request->user()->id;

        $personnel = (new CreateWithDocumentsAction)->execute(Personnel::class, $entityData, $documents);
        $personnel->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return (new PersonnelResource($personnel))->response()->setStatusCode(201);
    }

    public function update(Request $request, $id): JsonResponse|PersonnelResource
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $personnel = Personnel::findOrFail($id);
        $data = $request->validate(self::RULES);

        if ($request->hasFile('photo')) {
            if ($personnel->photo) {
                Storage::disk('public')->delete($personnel->photo);
            }

            $data['photo'] = $request->file('photo')->store('personnels/photos', 'public');
        } else {
            unset($data['photo']);
        }

        $data['updated_by'] = $request->user()->id;

        $personnel->update($data);
        $personnel->refresh()->load('transporter:id,company_name')->loadCount('logisticDocuments');

        return new PersonnelResource($personnel);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $personnel = Personnel::findOrFail($id);
        $photo = $personnel->photo;

        $this->deleteWithLogisticDocumentFiles($personnel);

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return response()->json(null, 204);
    }
}
