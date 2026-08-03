<?php

namespace App\Support;

class ImpersonationToken
{
    public static function encode(int $adminId): string
    {
        return "impersonation:{$adminId}";
    }

    public static function decode(string $tokenName): ?int
    {
        if (! preg_match('/^impersonation:(\d+)$/', $tokenName, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
