<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerDocumentRequest;
use App\Http\Requests\Customer\UpdateCustomerDocumentRequest;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use App\Services\CustomerFileNamingService;
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

        // Gak dipaginasi -- jumlahnya dibatasi customer_document_types yang aktif,
        // bukan data transaksional yang bisa terus tumbuh.
        $query = $customer->documents()->with(['documentType', 'uploadedBy']);

        if ($request->filled('id_lcr')) {
            $query->where('id_lcr', $request->integer('id_lcr'));
        }

        $documents = $query->orderByDesc('uploaded_at')->get();

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
        $documentType = CustomerDocumentType::findOrFail($data['id_document_type']);

        [$folder, $fileName] = CustomerFileNamingService::build(
            $customer,
            $documentType,
            $data['notes'] ?? null,
            $file->getClientOriginalExtension(),
        );
        $path = $file->storeAs($folder, $fileName, 'public');

        // id_lcr/notes opsional, keisi kalau dokumennya foto site LCR (id_document_type
        // salah satu kode lcr_*); null buat dokumen customer generik lainnya (NIB/NPWP/dst).
        $document = CustomerDocument::create([
            'id_customer'      => $customer->id_customer,
            'id_document_type' => $data['id_document_type'],
            'document_number'  => $data['document_number'] ?? null,
            'id_lcr'           => $data['id_lcr'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'file_path'        => $path,
            'file_name'        => $fileName,
            'uploaded_at'      => now(),
            'uploaded_by'      => $user->id,
        ]);

        $document->load(['documentType', 'uploadedBy']);

        return response()->json($this->formatDocument($document), 201);
    }

    public function update(UpdateCustomerDocumentRequest $request, Customer $customer, CustomerDocument $document)
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

        $data = $request->validated();
        $document->update($data);

        // Kalau caption-nya berubah, nama file fisik ikut di-rename juga --
        // rename beneran di storage, bukan cuma update kolom DB.
        if (array_key_exists('notes', $data)) {
            $this->renameDocumentFile($customer, $document);
        }

        return response()->json($this->formatDocument($document->fresh(['documentType', 'uploadedBy'])));
    }

    private function renameDocumentFile(Customer $customer, CustomerDocument $document): void
    {
        $document->loadMissing('documentType');

        if (!$document->documentType || !$document->file_path) {
            return;
        }

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);

        [$folder, $fileName] = CustomerFileNamingService::build($customer, $document->documentType, $document->notes, $extension);
        $newPath = "{$folder}/{$fileName}";

        if ($newPath === $document->file_path) {
            return;
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->move($document->file_path, $newPath);
        }

        $document->update(['file_path' => $newPath, 'file_name' => $fileName]);
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
            'id'               => $document->id_document,
            'id_customer'      => $document->id_customer,
            'id_document_type' => $document->id_document_type,
            'document_type'    => $document->documentType ? [
                'id'              => $document->documentType->id,
                'code'            => $document->documentType->code,
                'name'            => $document->documentType->name,
                'requires_number' => $document->documentType->requires_number,
            ] : null,
            'document_number' => $document->document_number,
            'id_lcr'      => $document->id_lcr,
            'notes'       => $document->notes,
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
