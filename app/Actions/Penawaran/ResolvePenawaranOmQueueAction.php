<?php

namespace App\Actions\Penawaran;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// antrian OM: nunggu OM/approved/ditolak OM aja -- ditolak BM gak pernah sampai OM, cuma nongol di menu BM
class ResolvePenawaranOmQueueAction
{
    public function execute(string $modelClass, ?string $search, int $perPage): LengthAwarePaginator
    {
        $query = $modelClass::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [3, 4, 6]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                    ->orWhere('kepada', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
