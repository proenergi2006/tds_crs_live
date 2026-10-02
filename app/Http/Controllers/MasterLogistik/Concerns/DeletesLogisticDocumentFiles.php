<?php

namespace App\Http\Controllers\MasterLogistik\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait DeletesLogisticDocumentFiles
{
    protected function deleteWithLogisticDocumentFiles(Model $parent): void
    {
        $filePaths = $parent->logisticDocuments->pluck('file_path')->filter();

        $parent->delete();

        $filePaths->each(fn (string $path) => Storage::disk('public')->delete($path));
    }
}
