<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreCustomerDocumentTypeRequest;
use App\Http\Requests\MasterData\UpdateCustomerDocumentTypeRequest;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return response()->json(
                $q->get()->map(fn (CustomerDocumentType $type) => $this->withIdAlias($type))
            );
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return response()->json($q->paginate($perPage));
    }

    public function store(StoreCustomerDocumentTypeRequest $request)
    {
        $documentType = CustomerDocumentType::create($request->validated());

        return response()->json($this->withIdAlias($documentType), 201);
    }

    public function show(CustomerDocumentType $customerDocumentType)
    {
        return response()->json($this->withIdAlias($customerDocumentType));
    }

    public function update(UpdateCustomerDocumentTypeRequest $request, CustomerDocumentType $customerDocumentType)
    {
        $documentType = $customerDocumentType;
        $documentType->update($request->validated());

        return response()->json($this->withIdAlias($documentType->fresh()));
    }

    private function withIdAlias(CustomerDocumentType $type): array
    {
        return array_merge($type->toArray(), ['id' => $type->id_document_type]);
    }

    public function destroy(CustomerDocumentType $customerDocumentType)
    {
        $documentType = $customerDocumentType;

        DB::transaction(function () use ($documentType) {
            CustomerDocument::where('id_document_type', $documentType->id_document_type)
                ->update([
                    'id_document_type' => null,
                    'document_name'    => $documentType->name,
                ]);

            $documentType->delete();
        });

        return response()->json(null, 204);
    }
}
