<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
{
    $q = Produk::with('ukuran.satuan','jenis');

    if ($s = $request->query('search')) {
        $q->where('nama_produk', 'like', "%{$s}%");
    }

    // Ambil parameter urut (opsional), beri default newest first
    $sortBy  = $request->query('sort_by', 'id_produk');     // atau 'created_time'
    $sortDir = $request->query('sort_dir', 'desc');          // 'asc' / 'desc'

    $q->orderBy($sortBy, $sortDir);

    $perPage = (int) $request->query('per_page', 10);
    return response()->json($q->paginate($perPage));
}

    public function store(Request $request)
{
    $data = $request->validate([
        'nama_produk'  => 'required|string|max:255',
        'merk_dagang'  => 'nullable|string|max:255',
        'deskripsi'    => 'nullable|string',
        'id_ukuran'    => 'required|exists:ukurans,id_ukuran',
        'id_jenis'     => 'required|exists:jenis_produks,id_jenis', 
        'is_active'    => 'sometimes|boolean',
    ]);

    $data['created_time'] = now();
    $data['created_by']   = $request->user()->name;

    $produk = Produk::create($data);
    return response()->json($produk, 201);
}


    public function show($id)
    {
        $produk = Produk::with('ukuran')->findOrFail($id);
        return response()->json($produk);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nama_produk'  => 'required|string|max:255',
            'merk_dagang'  => 'nullable|string|max:255',
            'deskripsi'    => 'nullable|string',
            'id_ukuran'    => 'required|exists:ukurans,id_ukuran',
            'id_jenis'     => 'required|exists:jenis_produks,id_jenis', // ✅ tambahkan ini
            'is_active'    => 'sometimes|boolean',
        ]);

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;

        $prod = Produk::findOrFail($id);
        $prod->update($data);

        return response()->json($prod);
    }

    public function destroy($id)
    {
        Produk::destroy($id);
        return response()->json(null, 204);
    }
}
