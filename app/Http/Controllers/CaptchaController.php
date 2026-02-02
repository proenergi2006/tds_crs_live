<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function generate()
    {
        $key  = Str::uuid()->toString();
        $code = strtoupper(Str::random(5)); // 5 huruf/angka

        // simpan kode 10 menit
        Cache::put("captcha:$key", $code, now()->addMinutes(10));

        $svg = $this->renderSvg($code);
        $image = 'data:image/svg+xml;base64,'.base64_encode($svg);

        return response()->json([
            'key'   => $key,
            'image' => $image,
        ]);
    }

    private function renderSvg(string $text): string
    {
        // SVG very simple + sedikit noise
        $w = 220; $h = 60;
        $lines = '';
        for ($i=0; $i<6; $i++) {
            $x1 = rand(0,$w); $y1 = rand(0,$h);
            $x2 = rand(0,$w); $y2 = rand(0,$h);
            $col = sprintf('#%06X', rand(0,0xAAAAAA));
            $lines .= "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='$col' stroke-width='1' opacity='0.4'/>";
        }
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h">
  <rect width="100%" height="100%" fill="#F3F4F6"/>
  $lines
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
        font-size="32" font-family="monospace" fill="#111827" letter-spacing="4">
    $text
  </text>
</svg>
SVG;
    }
}
