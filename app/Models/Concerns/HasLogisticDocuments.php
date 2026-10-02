<?php

namespace App\Models\Concerns;

use App\Models\LogisticDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLogisticDocuments
{
    public static function bootHasLogisticDocuments(): void
    {
        static::deleting(fn(Model $model) => $model->logisticDocuments()->delete());
    }

    public function logisticDocuments(): MorphMany
    {
        return $this->morphMany(LogisticDocument::class, 'documentable');
    }

    public function documentNamingLabel(): string
    {
        return (string) $this->name;
    }
}
