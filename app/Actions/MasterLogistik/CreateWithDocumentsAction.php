<?php

namespace App\Actions\MasterLogistik;

use App\Services\MasterLogistik\LogisticDocumentFileNamer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateWithDocumentsAction
{
    public function execute(string $modelClass, array $entityData, array $documents): Model
    {
        return DB::transaction(function () use ($modelClass, $entityData, $documents) {
            $entity = $modelClass::create($entityData);
            $documentableType = $entity->getMorphClass();

            foreach ($documents as $document) {
                $this->storeDocument($entity, $documentableType, $document);
            }

            return $entity->fresh('logisticDocuments');
        });
    }

    private function storeDocument(Model $entity, string $documentableType, array $document): void
    {
        $file = $document['file'];
        $path = Storage::disk('public')->putFile("logistic-documents/{$documentableType}", $file);

        $entity->logisticDocuments()->create([
            'document_type' => $document['document_type'],
            'file_path' => $path,
            'file_name' => LogisticDocumentFileNamer::build($entity, $document['document_type'], $file),
            'valid_until' => $document['valid_until'] ?? null,
            'uploaded_at' => now(),
            'uploaded_by' => auth()->id(),
        ]);
    }
}
