<?php
// app/Http/Controllers/SalesConfirmationController.php

namespace App\Http\Controllers;

use App\Models\PoCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesConfirmationController extends Controller
{
    /**
     * GET /api/sales-confirmations
     * List PO Customer dengan disposisi_poc = 1 (siap Sales Confirmation)
     * Query params:
     *  - per_page (default 10)
     *  - search   (cari di nomor_poc, nomor_penawaran, nama customer)
     *  - date_from, date_to (filter tanggal_poc)
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('search', ''));
        $likeOp  = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $q = PoCustomer::query()
            ->with([
                'customer:id_customer,nama_perusahaan',
                'penawaran:id_penawaran,nomor_penawaran,masa_berlaku,sampai_dengan',
                // kalau perlu item & atribut produk:
                'penawaran.items.produk.jenis',
                'penawaran.items.produk.ukuran.satuan',
            ])
            ->where('disposisi_poc', 1);

        if ($search !== '') {
            $q->where(function ($qq) use ($search, $likeOp) {
                $qq->where('nomor_poc', $likeOp, "%{$search}%")
                   ->orWhereHas('customer', fn ($c) => $c->where('nama_perusahaan', $likeOp, "%{$search}%"))
                   ->orWhereHas('penawaran', fn ($p) => $p->where('nomor_penawaran', $likeOp, "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $q->whereDate('tanggal_poc', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('tanggal_poc', '<=', $request->date('date_to'));
        }

        return response()->json(
            $q->orderByDesc('id_poc')->paginate($perPage)
        );
    }

    /**
     * GET /api/sales-confirmations/{id_poc}
     * Detail satu PO (hanya bila disposisi_poc = 1)
     */
    public function show($id_poc)
    {
        $po = PoCustomer::with([
                'customer',
                'penawaran',
                'penawaran.items.produk.jenis',
                'penawaran.items.produk.ukuran.satuan',
            ])
            ->where('disposisi_poc', 1)
            ->findOrFail($id_poc);

        return response()->json($po);
    }
}
