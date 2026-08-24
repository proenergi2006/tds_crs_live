<?php

namespace App\Actions\Penawaran;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// antrian BM: menunggu BM/OM, approved, dan ditolak (BM/OM)
class ResolvePenawaranBmQueueAction
{
    public function execute(string $modelClass, ?string $search, int $perPage): LengthAwarePaginator
    {
        $query = $modelClass::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [2, 3, 4, 5, 6]);

        if ($search) {
            // Kontak tujuan bukan kolom sendiri lagi; search menjangkau nama perusahaan customer + nama kontaknya.
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('customerContact', fn ($cq) => $cq->where('full_name', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
