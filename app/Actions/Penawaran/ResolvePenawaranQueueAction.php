<?php

namespace App\Actions\Penawaran;

use App\Enums\PenawaranDisposisi;
use App\Models\Penawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResolvePenawaranQueueAction
{
    private const DISPOSISI_BY_STEP = [
        'bm' => [
            PenawaranDisposisi::MenungguVerifikasiBm->value,
            PenawaranDisposisi::MenungguVerifikasiOm->value,
            PenawaranDisposisi::DisetujuiOm->value,
            PenawaranDisposisi::DitolakBm->value,
            PenawaranDisposisi::DitolakOm->value,
        ],
        'om' => [
            PenawaranDisposisi::MenungguVerifikasiOm->value,
            PenawaranDisposisi::DisetujuiOm->value,
            PenawaranDisposisi::DitolakOm->value,
        ],
    ];

    public function execute(string $brand, string $step, ?string $search, int $perPage, ?bool $polimerOnly = null): LengthAwarePaginator
    {
        $query = Penawaran::where('brand', $brand)
            ->with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', self::DISPOSISI_BY_STEP[$step]);

        if ($polimerOnly !== null) {
            $query->{$polimerOnly ? 'whereHas' : 'whereDoesntHave'}(
                'items.produk', fn ($q) => $q->where('id_jenis', 11)
            );
        }

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
