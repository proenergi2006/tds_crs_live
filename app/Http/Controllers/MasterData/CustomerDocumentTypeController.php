<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreCustomerDocumentTypeRequest;
use App\Http\Requests\MasterData\UpdateCustomerDocumentTypeRequest;
use App\Models\CustomerDocumentType;
use Illuminate\Http\Request;

class CustomerDocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $q = CustomerDocumentType::query();

        if ($search = $request->query('search')) {
            $q->where(function ($q2) use ($search) {
                $q2->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $q->orderBy('name');

        if ($request->boolean('as_list')) {
            // Primary key kolomnya id_document_type (bukan id) -- FE (Customer/Detail.vue)
            // match dokumen ke tipe lewat `id`, jadi di-alias di sini tanpa
            // menghapus field aslinya (non-breaking untuk consumer lain).
            return response()->json(
                $q->get()->map(fn (CustomerDocumentType $type) => array_merge($type->toArray(), ['id' => $type->id_document_type]))
            );
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return response()->json($q->paginate($perPage));
    }

    public function store(StoreCustomerDocumentTypeRequest $request)
    {
        $documentType = CustomerDocumentType::create($request->validated());

        return response()->json($documentType, 201);
    }

    public function show($id)
    {
        $documentType = CustomerDocumentType::findOrFail($id);

        return response()->json($documentType);
    }

    public function update(UpdateCustomerDocumentTypeRequest $request, $id)
    {
        $documentType = CustomerDocumentType::findOrFail($id);
        $documentType->update($request->validated());

        return response()->json($documentType->fresh());
    }

    public function destroy($id)
    {
        $documentType = CustomerDocumentType::findOrFail($id);
        $documentType->delete();

        return response()->json(null, 204);
    }
}
