<?php

namespace App\Casts;

use App\Enums\PersonnelDocumentType;
use App\Enums\TransporterDocumentType;
use App\Enums\TruckDocumentType;
use App\Enums\VesselDocumentType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class LogisticDocumentTypeCast implements CastsAttributes
{
    public static function enumClassFor(string $documentableType): string
    {
        return match ($documentableType) {
            'transporter' => TransporterDocumentType::class,
            'personnel' => PersonnelDocumentType::class,
            'vessel' => VesselDocumentType::class,
            'truck' => TruckDocumentType::class,
            default => throw new InvalidArgumentException("Unknown documentable type: {$documentableType}"),
        };
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?\BackedEnum
    {
        if ($value === null) {
            return null;
        }

        return self::enumClassFor($attributes['documentable_type'])::from($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => $value instanceof \BackedEnum ? $value->value : $value];
    }
}
