<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Casts\LogisticDocumentTypeCast;
use App\Http\Controllers\Controller;
use App\Http\Resources\MasterLogistik\LogisticDocumentResource;
use App\Models\Personnel;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\Vessel;
use App\Services\MasterLogistik\LogisticDocumentFileNamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LogisticDocumentController extends Controller
{
    private const PARENT_MODELS = [
        'transporters' => Transporter::class,
        'personnels' => Personnel::class,
        'vessels' => Vessel::class,
        'trucks' => Truck::class,
    ];

    public function index(Request $request, string $parentType, $parentId): JsonResponse|AnonymousResourceCollection
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $parent = (self::PARENT_MODELS[$parentType])::findOrFail($parentId);

        return LogisticDocumentResource::collection(
            $parent->logisticDocuments()->with('uploader:id,name')->latest()->get()
        );
    }

    public function store(Request $request, string $parentType, $parentId): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $parent = (self::PARENT_MODELS[$parentType])::findOrFail($parentId);

        $enumClass = LogisticDocumentTypeCast::enumClassFor($parent->getMorphClass());

        $data = $request->validate([
            'document_type' => ['required', 'string', Rule::enum($enumClass)],
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'valid_until' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $path = Storage::disk('public')->putFile("logistic-documents/{$parent->getMorphClass()}", $file);

        $document = $parent->logisticDocuments()->create([
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'file_name' => LogisticDocumentFileNamer::build($parent, $data['document_type'], $file),
            'valid_until' => $data['valid_until'] ?? null,
            'uploaded_at' => now(),
            'uploaded_by' => $request->user()->id,
        ]);

        $document->load('uploader:id,name');

        return (new LogisticDocumentResource($document))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $parentType, $parentId, $documentId): JsonResponse
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $parent = (self::PARENT_MODELS[$parentType])::findOrFail($parentId);

        $document = $parent->logisticDocuments()->whereKey($documentId)->firstOrFail();

        $path = $document->file_path;
        $document->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(null, 204);
    }
}
