<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerDocumentRequest;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    public function index(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Not paginated: bounded by active customer_document_types count, not
        // transactional data that grows unbounded.
        $documents = $customer->documents()
            ->with(['documentType', 'uploadedBy'])
            ->orderByDesc('uploaded_at')
            ->get();

        return response()->json(
            $documents->map(fn (CustomerDocument $document) => $this->formatDocument($document))->values()
        );
    }

    public function store(StoreCustomerDocumentRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $file = $request->file('file');
        $path = $file->store("customer_documents/{$customer->id_customer}", 'public');

        $document = CustomerDocument::create([
            'id_customer'      => $customer->id_customer,
            'id_document_type' => $data['id_document_type'],
            'document_number'  => $data['document_number'] ?? null,
            'file_path'        => $path,
            'file_name'        => $file->getClientOriginalName(),
            'uploaded_at'      => now(),
            'uploaded_by'      => $user->id,
        ]);

        $document->load(['documentType', 'uploadedBy']);

        return response()->json($this->formatDocument($document), 201);
    }

    public function destroy(Request $request, Customer $customer, CustomerDocument $document)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($document->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Dokumen tidak ditemukan untuk customer ini.'], 404);
        }

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    private function formatDocument(CustomerDocument $document): array
    {
        return [
            'id'               => $document->id,
            'id_customer'      => $document->id_customer,
            'id_document_type' => $document->id_document_type,
            'document_type'    => $document->documentType ? [
                'id'              => $document->documentType->id,
                'code'            => $document->documentType->code,
                'name'            => $document->documentType->name,
                'requires_number' => $document->documentType->requires_number,
            ] : null,
            'document_number' => $document->document_number,
            'file_name'   => $document->file_name,
            'file_path'   => $document->file_path,
            'url'         => $document->file_path ? Storage::disk('public')->url($document->file_path) : null,
            'uploaded_at' => optional($document->uploaded_at)->toISOString(),
            'uploaded_by' => $document->uploadedBy ? [
                'id'   => $document->uploadedBy->id,
                'name' => $document->uploadedBy->name,
            ] : null,
        ];
    }
}
