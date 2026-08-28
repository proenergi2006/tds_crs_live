<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateNumericCode(int $length = 8): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');
        return (string) random_int($min, $max);
    }

    public function generatePng(string|array $payload, int $idPenawaran): array
    {
        config(['qrcode.image_backend' => 'gd']);

        $data = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_SLASHES)
            : (string) $payload;

        $pngBinary = QrCode::format('png')
            ->size(512)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($data);

        $dir  = 'qrcodes/' . now()->format('Y/m');
        $safe = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        $name = "{$safe}_" . now()->format('YmdHis') . ".png";

        Storage::disk('public')->put("$dir/$name", $pngBinary);

        $abs = public_path("storage/$dir/$name");
        return [
            'abs_for_pdf' => 'file://' . $abs,
            'url'         => asset("storage/$dir/$name"),
            'rel'         => "$dir/$name",
        ];
    }

    public function generateSvg(string|array $payload, int $idPenawaran): array
    {
        $data = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_SLASHES)
            : (string) $payload;

        $svg = QrCode::format('svg')
            ->size(512)->margin(1)->errorCorrection('M')
            ->generate($data);

        $dir  = 'qrcodes/' . now()->format('Y/m');
        $safe = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        $name = "{$safe}_" . now()->format('YmdHis') . ".svg";

        Storage::disk('public')->put("$dir/$name", $svg);

        $abs = public_path("storage/$dir/$name");
        return [
            'abs_for_pdf' => 'file://' . $abs,
            'url'         => asset("storage/$dir/$name"),
            'rel'         => "$dir/$name",
            'svg'         => $svg,
        ];
    }

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
