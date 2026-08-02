<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * customer_code belum pernah di-generate di manapun (udah dicek langsung, semua
 * row live sekarang masih ''). Sequential dengan prefix tetap, formatnya
 * CUST-00001. Pakai lockForUpdate() biar 2 submit onboarding yang barengan gak
 * kebagian nomor yang sama -- caller wajib panggil ini di dalam DB::transaction()
 * yang udah jalan, method ini sendiri gak buka transaction baru.
 */
class CustomerCodeGenerator
{
    private const PREFIX = 'CUST-';
    private const PAD_LENGTH = 5;

    public static function generate(): string
    {
        // Postgres gak ngizinin FOR UPDATE bareng fungsi agregat (MAX()), jadi
        // sebagai gantinya kita lock baris "terakhir" (ORDER BY + LIMIT 1). Di
        // READ COMMITTED (default Postgres), transaction kedua yang nunggu lock
        // ini otomatis baca versi terbaru pas lock-nya lepas -- jadi tetap aman
        // dari race condition, asal semua caller lewat method ini.
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
