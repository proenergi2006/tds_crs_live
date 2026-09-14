<?php

namespace App\Enums;

enum PoCustomerScProcessState: string
{
    case Blocked = 'blocked';
    case Cleared = 'cleared';

    public function label(): string
    {
        return match ($this) {
            self::Blocked => 'Block SC',
            self::Cleared => 'Lolos Gerbang SC',
        };
    }
}
