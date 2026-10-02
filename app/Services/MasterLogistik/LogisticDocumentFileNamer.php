<?php

namespace App\Services\MasterLogistik;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class LogisticDocumentFileNamer
{
    public static function build(Model $parent, string $documentType, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();

        $segments = [
            $parent->getMorphClass(),
            self::sanitize($parent->documentNamingLabel()),
            self::sanitize($documentType),
        ];

        return implode('_', array_filter($segments)) . '.' . $extension;
    }

    private static function sanitize(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_');
    }
}
