<?php

namespace App\Actions\Penawaran;

use App\Models\Penawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResolvePenawaranQueueAction
{
    private const DISPOSISI_BY_STEP = [
        'bm' => [2, 3, 4, 5, 6],
        'om' => [3, 4, 6],
    ];

    public function execute(string $brand, string $step, ?string $search, int $perPage): LengthAwarePaginator
    {
        $query = Penawaran::where('brand', $brand)
            ->with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', self::DISPOSISI_BY_STEP[$step]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('customerContact', fn($cq) => $cq->where('full_name', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
