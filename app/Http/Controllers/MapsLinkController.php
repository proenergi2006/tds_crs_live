<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapsLinkController extends Controller
{
    // Cuma domain resmi Google Maps. URL yang dikirim maupun hasil resolve redirect
    // harus berakhir di salah satu domain ini -- biar endpoint ini gak bisa dipakai
    // buat proxy fetch ke URL sembarangan (SSRF).
    private const ALLOWED_HOSTS = [
        'maps.app.goo.gl',
        'goo.gl',
        'google.com',
        'www.google.com',
        'maps.google.com',
    ];

    private const COORD_PATTERNS = [
        '/[?&]query=(-?\d+\.?\d*),(-?\d+\.?\d*)/',
        '/@(-?\d+\.?\d*),(-?\d+\.?\d*)/',
        // Ini format asli hasil resolve short link maps.app.goo.gl:
        // /maps/search/LAT,+LNG (spasinya di-encode "+", bukan query param).
        '#/maps/search/(-?\d+\.?\d*),\+?(-?\d+\.?\d*)#',
    ];

    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048|url',
        ]);

        $url = $validated['url'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (!in_array($scheme, ['http', 'https'], true) || !$host || !$this->isAllowedHost($host)) {
            return response()->json(['message' => 'URL harus link Google Maps yang valid.'], 422);
        }

        try {
            $response = Http::timeout(5)
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($url);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal mengakses link.'], 422);
        }

        // Header ini bisa cuma berisi 1 URL (1x redirect) yang isinya sendiri udah
        // ada komanya (mis. path "/maps/search/-6.23,+106.83"), jadi jangan asal
        // split di koma. Kalau memang ada >1 value yang digabung Laravel/Guzzle
        // (getHeaderLine()), pemisahnya selalu ", " (koma+spasi, sesuai HTTP header
        // folding) -- sedangkan URL-nya sendiri gak pernah punya spasi mentah (selalu
        // di-encode %20/+), jadi split di ", " aman, gak bakal motong isi URL.
        $redirectHistory = $response->header('X-Guzzle-Redirect-History');
        $finalUrl = $redirectHistory ? trim(last(explode(', ', $redirectHistory))) : $url;
        $finalHost = parse_url($finalUrl, PHP_URL_HOST);

        if (!$finalHost || !$this->isAllowedHost($finalHost)) {
            return response()->json(['message' => 'Link redirect ke luar domain Google Maps.'], 422);
        }

        // Koordinatnya biasanya ada di URL akhir, tapi kalau gak ketemu di situ
        // dicoba cari di body halaman -- share page kadang naruh koordinat di
        // canonical link/meta, bukan di URL redirect terakhir.
        $coords = $this->extractCoords($finalUrl) ?? $this->extractCoords($response->body());

        if (!$coords) {
            return response()->json(['message' => 'Koordinat tidak ditemukan di link ini.'], 422);
        }

        return response()->json($coords);
    }

    private function isAllowedHost(string $host): bool
    {
        $host = strtolower($host);
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }
        return false;
    }

    private function extractCoords(string $subject): ?array
    {
        foreach (self::COORD_PATTERNS as $pattern) {
            if (preg_match($pattern, $subject, $m)) {
                return ['lat' => (float) $m[1], 'lng' => (float) $m[2]];
            }
        }
        return null;
    }
}
