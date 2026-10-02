<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PublicAttachmentFormatter
{
    public static function format(?array $attachments): array
    {
        return collect($attachments ?? [])
            ->map(function (array $attachment) {
                $path = $attachment['path'] ?? null;
                $exists = $path && Storage::disk('public')->exists($path);

                return [
                    'path'          => $path,
                    'original_name' => $attachment['original_name'] ?? null,
                    'url'           => $exists ? Storage::disk('public')->url($path) : null,
                    'size_bytes'    => $exists ? Storage::disk('public')->size($path) : null,
                ];
            })
            ->values()
            ->all();
    }
}
