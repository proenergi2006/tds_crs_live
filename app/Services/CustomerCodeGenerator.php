<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CustomerCodeGenerator
{
    private const PREFIX = 'CUST-';
    private const PAD_LENGTH = 5;

    public static function generate(): string
    {
        // postgres gak bolehin FOR UPDATE bareng MAX(), makanya lock baris terakhir doang (order by + limit 1)
        $lastCode = DB::table('customers')
            ->where('customer_code', 'like', self::PREFIX.'%')
            ->orderByDesc('customer_code')
            ->lockForUpdate()
            ->limit(1)
            ->value('customer_code');

        $nextNumber = $lastCode ? ((int) substr($lastCode, strlen(self::PREFIX))) + 1 : 1;

        return self::PREFIX.str_pad((string) $nextNumber, self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }
}
