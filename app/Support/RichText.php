<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

class RichText
{
    public static function purify(string $html): string
    {
        return (string) Purifier::clean($html, 'ckeditor');
    }

    public static function purifyOrNull(?string $html): ?string
    {
        if ($html === null || self::isBlank($html)) {
            return null;
        }

        return self::purify($html);
    }

    public static function isBlank(string $html): bool
    {
        $text = html_entity_decode(strip_tags(self::purify($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim($text) === '';
    }
}
