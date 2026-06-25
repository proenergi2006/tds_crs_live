<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterData\ProdukHargaPeriodeResource;
use App\Http\Resources\MasterData\ProdukHargaResource;
use App\Models\ProdukHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class ProdukHargaController extends Controller
{
    public function index(Request $request)
    {
        $q = ProdukHarga::with(['cabang', 'produk.ukuran.satuan']);

        if ($cabangId = $request->query('id_cabang')) {
            $q->where('id_cabang', $cabangId);
        }

        if ($produkId = $request->query('id_produk')) {
            $q->where('id_produk', $produkId);
        }

        if ($periodeAwal = $request->query('periode_awal')) {
            $q->whereDate('periode_awal', $periodeAwal);
        }

        if ($periodeAkhir = $request->query('periode_akhir')) {
            $q->whereDate('periode_akhir', $periodeAkhir);
        }

        if ($s = $request->query('search')) {
            $q->where(function ($q2) use ($s) {
                $q2->whereHas('produk', fn($q3) => $q3->where('nama_produk', 'like', "%{$s}%"))
                    ->orWhereHas('cabang', fn($q4) => $q4->where('nama_cabang', 'like', "%{$s}%"));
            });
        }

        $q->orderBy('periode_awal', 'desc')
            ->orderBy('periode_akhir', 'desc')
            ->orderBy('created_time', 'desc');

        if ($request->boolean('as_list')) {
            return ProdukHargaResource::collection($q->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return ProdukHargaResource::collection($q->paginate($perPage));
    }

    public function periode(Request $request)
    {
        $rows = ProdukHarga::query()
            ->selectRaw(
                'periode_awal, periode_akhir, ' .
                    'COUNT(*) AS jumlah_data, ' .
                    'COUNT(DISTINCT id_cabang) AS jumlah_cabang, ' .
                    'MAX(COALESCE(lastupdate_time, created_time)) AS terakhir_diupdate'
            )
            ->groupBy('periode_awal', 'periode_akhir')
            ->orderBy('periode_awal', 'desc')
            ->orderBy('periode_akhir', 'desc')
            ->get();

        return ProdukHargaPeriodeResource::collection($rows);
    }

    public function check(Request $request)
    {
        $produkId = $request->query('produk_id');
        $tanggal = $request->query('tanggal');

        $harga = DB::table('produk_hargas')
            ->where('id_produk', $produkId)
            ->whereDate('periode_awal', '<=', $tanggal)
            ->whereDate('periode_akhir', '>=', $tanggal)
            ->orderByDesc('periode_akhir')
            ->first();

        return response()->json([
            'harga_price_list' => $harga?->harga_price_list ?? null,
            'found' => (bool) $harga,
        ]);
    }

    // Mengembalikan map {id_produk: harga} untuk produk yang harganya ter-cover oleh
    // rentang periode pengiriman. Jika satu produk punya >1 harga di rentang itu,
    // ambil yang paling baru (periode_akhir paling lama).
    //
    // Param `pe=1` (Proenergi): pakai harga_price_list_pe, fallback ke harga_price_list
    // bila harga PE belum diisi (<= 0). Default (TDS): pakai harga_price_list.
    public function byDate(Request $request)
    {
        $awal  = $request->query('periode_awal');
        $akhir = $request->query('periode_akhir') ?: $awal;
        $usePe = $request->boolean('pe');

        if (!$awal) {
            return response()->json([]);
        }

        // Overlap: harga berlaku selama rentang pengiriman bila
        // periode_awal harga <= akhir pengiriman DAN periode_akhir harga >= awal pengiriman.
        $q = DB::table('produk_hargas')
            ->whereDate('periode_awal', '<=', $akhir)
            ->whereDate('periode_akhir', '>=', $awal)
            ->orderByDesc('periode_akhir');

        if ($cabangId = $request->query('id_cabang')) {
            $q->where('id_cabang', $cabangId);
        }

        $cols = $usePe
            ? ['id_produk', 'harga_price_list', 'harga_price_list_pe']
            : ['id_produk', 'harga_price_list'];
        $rows = $q->get($cols);

        $map = [];
        foreach ($rows as $row) {
            // Karena sudah di-order periode_akhir desc, baris pertama per produk = harga terbaru.
            if (array_key_exists($row->id_produk, $map)) {
                continue;
            }

            // Proenergi: harga_price_list_pe ?? harga_price_list. Kolom pe nullable &
            // default 0, jadi nilai <= 0 dianggap "belum diisi" lalu fallback ke TDS.
            $map[$row->id_produk] = ($usePe && (float) ($row->harga_price_list_pe ?? 0) > 0)
                ? $row->harga_price_list_pe
                : $row->harga_price_list;
        }

        return response()->json($map);
    }

    public function store(Request $request)
    {
        $request->merge(array_map(fn($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'periode_awal'        => 'required|date',
            'periode_akhir'       => 'required|date|after_or_equal:periode_awal',
            'id_cabang'           => 'required|exists:cabangs,id_cabang',
            'id_produk'           => 'required|exists:produks,id_produk',
            'harga_price_list'    => 'nullable|numeric|min:0',
            'harga_price_list_pe' => 'nullable|numeric|min:0',
            'harga_bm'            => 'nullable|numeric|min:0',
            'harga_cogs'          => 'nullable|numeric|min:0',
            'harga_margin'        => 'nullable|numeric|min:0',
            'harga_om'            => 'nullable|numeric|min:0',
            'harga_ceo'           => 'nullable|numeric|min:0',
            'catatan'             => 'nullable|string',
        ]);

        foreach (['harga_price_list', 'harga_price_list_pe', 'harga_bm', 'harga_cogs', 'harga_margin', 'harga_om'] as $k) {
            $data[$k] = $data[$k] ?? 0;
        }

        $data['created_time'] = now();
        $data['created_by']   = $request->user()?->name ?? 'system';

        $ph = ProdukHarga::create($data);

        return new ProdukHargaResource($ph->load(['cabang', 'produk.ukuran.satuan']));
    }

    public function show($id)
    {
        $ph = ProdukHarga::with(['cabang', 'produk.ukuran.satuan'])->findOrFail($id);

        return new ProdukHargaResource($ph);
    }

    public function update(Request $request, $id)
    {
        $request->merge(array_map(fn($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'periode_awal'        => 'required|date',
            'periode_akhir'       => 'required|date|after_or_equal:periode_awal',
            'id_cabang'           => 'required|exists:cabangs,id_cabang',
            'id_produk'           => 'required|exists:produks,id_produk',
            'harga_price_list'    => 'nullable|numeric|min:0',
            'harga_price_list_pe' => 'nullable|numeric|min:0',
            'harga_bm'            => 'nullable|numeric|min:0',
            'harga_cogs'          => 'nullable|numeric|min:0',
            'harga_margin'        => 'nullable|numeric|min:0',
            'harga_om'            => 'nullable|numeric|min:0',
            'harga_ceo'           => 'nullable|numeric|min:0',
            'catatan'             => 'nullable|string',
        ]);

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()?->name ?? 'system';

        $ph = ProdukHarga::findOrFail($id);
        $ph->update($data);

        return new ProdukHargaResource($ph->load(['cabang', 'produk.ukuran.satuan']));
    }

    public function destroy($id)
    {
        ProdukHarga::destroy($id);

        return response()->json(null, 204);
    }

    public function addMargin(Request $request)
    {
        $validated = $request->validate([
            'id_cabang'     => 'required|exists:cabangs,id_cabang',
            'id_produk'     => 'required|exists:produks,id_produk',
            'periode_awal'  => 'required|date',
            'periode_akhir' => 'required|date|after_or_equal:periode_awal',
            'margin'        => 'required|numeric|min:0',
        ]);

        $affected = ProdukHarga::where('id_cabang', $validated['id_cabang'])
            ->where('id_produk', $validated['id_produk'])
            ->whereBetween('periode_awal', [$validated['periode_awal'], $validated['periode_akhir']])
            ->get();

        if ($affected->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data harga yang cocok.'], 404);
        }

        foreach ($affected as $harga) {
            $harga->harga_price_list += $validated['margin'];
            $harga->harga_bm         += $validated['margin'];
            if (Schema::hasColumn('produk_hargas', 'margin')) {
                $harga->margin += $validated['margin'];
            }
            $harga->save();
        }

        return response()->json([
            'message' => 'Margin berhasil diterapkan ke ' . $affected->count() . ' data.',
        ]);
    }
}
