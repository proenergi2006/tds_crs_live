<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    public function deleteFiles(int $idPenawaran, ?string $qrUrl = null): void
    {
        if (!empty($qrUrl)) {
            $path = parse_url($qrUrl, PHP_URL_PATH);
            if ($path && str_starts_with($path, '/storage/')) {
                $rel = ltrim(substr($path, strlen('/storage/')), '/');
                Storage::disk('public')->delete($rel);
            }
        }

        $prefix = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        $all = Storage::disk('public')->allFiles('qrcodes');
        foreach ($all as $file) {
            $base = basename($file);
            if (str_starts_with($base, $prefix) && (str_ends_with($base, '.png') || str_ends_with($base, '.svg'))) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
