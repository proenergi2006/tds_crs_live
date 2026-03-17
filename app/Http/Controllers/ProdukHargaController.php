<?php

namespace App\Http\Controllers;

use App\Models\ProdukHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class ProdukHargaController extends Controller
{
    public function index(Request $request)
    {
        $q = VendorPo::with(['vendor', 'terminal', 'produks.produk']);
    
        if ($vendorId = $request->query('id_vendor')) {
            $q->where('id_vendor', $vendorId);
        }
    
        if ($terminalId = $request->query('id_terminal')) {
            $q->where('id_terminal', $terminalId);
        }
    
        if ($s = $request->query('search')) {
            $q->where(function ($q2) use ($s) {
                $q2->where('nomor_po', 'like', "%{$s}%")
                   ->orWhere('keterangan', 'like', "%{$s}%");
            });
        }
    
        $perPage = $request->query('per_page', 10);
    
        return response()->json(
            $q->orderBy('tanggal_inven', 'desc')
              ->orderBy('id_po', 'desc')
              ->paginate($perPage)
        );
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

    public function store(Request $request)
    {
        $request->merge(array_map(fn($v) => $v === '' ? null : $v, $request->all()));
    
        $data = $request->validate([
            'periode_awal'     => 'required|date',
            'periode_akhir'    => 'required|date|after_or_equal:periode_awal',
            'id_cabang'        => 'required|exists:cabangs,id_cabang',
            'id_produk'        => 'required|exists:produks,id_produk',
            'harga_price_list' => 'nullable|numeric|min:0',
            'harga_bm'         => 'nullable|numeric|min:0',
            'harga_cogs'       => 'nullable|numeric|min:0',
            'harga_margin'     => 'nullable|numeric|min:0',
            'harga_om'         => 'nullable|numeric|min:0',
            'harga_ceo'        => 'nullable|numeric|min:0',
            'catatan'          => 'nullable|string',
        ]);
    
        foreach (['harga_price_list', 'harga_bm', 'harga_cogs', 'harga_margin', 'harga_om'] as $k) {
            $data[$k] = $data[$k] ?? 0;
        }
    
        $data['created_time'] = now();
        $data['created_by'] = $request->user()?->name ?? 'system';
    
        $ph = ProdukHarga::create($data);
    
        return response()->json($ph, 201);
    }
    
    public function show($id)
    {
        $ph = ProdukHarga::with(['cabang', 'produk'])->findOrFail($id);
        return response()->json($ph);
    }

    public function update(Request $request, $id)
    {

        $request->merge(array_map(fn($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'periode_awal'     => 'required|date',
            'periode_akhir'    => 'required|date|after_or_equal:periode_awal',
            'id_cabang'        => 'required|exists:cabangs,id_cabang',
            'id_produk'        => 'required|exists:produks,id_produk',
            'harga_price_list' => 'nullable|numeric|min:0',
            'harga_bm'         => 'nullable|numeric|min:0',
            'harga_cogs'       => 'nullable|numeric|min:0',
            'harga_margin'     => 'nullable|numeric|min:0',
            'harga_om'         => 'nullable|numeric|min:0',
            'harga_ceo'        => 'nullable|numeric|min:0',
            'catatan'          => 'nullable|string',
        ]);

       

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()?->name ?? 'system';

        $ph = ProdukHarga::findOrFail($id);
        $ph->update($data);

        return response()->json($ph);
    }


    public function destroy($id)
    {
        ProdukHarga::destroy($id);
        return response()->json(null, 204);
    }

    public function addMargin(Request $request)
{
    $validated = $request->validate([
        'id_cabang'      => 'required|exists:cabangs,id_cabang',
        'id_produk'      => 'required|exists:produks,id_produk',
        'periode_awal'   => 'required|date',
        'periode_akhir'  => 'required|date|after_or_equal:periode_awal',
        'margin'         => 'required|numeric|min:0',
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
