<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('as_list')) {
            return response()->json(
                Produk::with('ukuran.satuan', 'jenis')
                    ->orderBy('nama_produk')
                    ->get()
            );
        }

        $q = Produk::with('ukuran.satuan', 'jenis');

        if ($s = $request->query('search')) {
            $q->where('nama_produk', 'ilike', "%{$s}%");
        }

        $sortBy  = $request->query('sort_by', 'id_produk');
        $sortDir = $request->query('sort_dir', 'desc');

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
            'id_ukuran'    => 'nullable|exists:ukurans,id_ukuran',
            'id_jenis'     => 'nullable|exists:jenis_produks,id_jenis',
            'is_active'    => 'sometimes|boolean',
        ]);

        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;

        $produk = Produk::create($data);
        return response()->json($produk->load('ukuran.satuan', 'jenis'), 201);
    }


    public function show(Produk $produk)
    {
        $produk->load('ukuran.satuan', 'jenis');
        return response()->json($produk);
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $request->validate([
            'nama_produk'  => 'required|string|max:255',
            'merk_dagang'  => 'nullable|string|max:255',
            'deskripsi'    => 'nullable|string',
            'id_ukuran'    => 'nullable|exists:ukurans,id_ukuran',
            'id_jenis'     => 'nullable|exists:jenis_produks,id_jenis',
            'is_active'    => 'sometimes|boolean',
        ]);

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;

        $produk->update($data);

        return response()->json($produk->load('ukuran.satuan', 'jenis'));
    }

    // destroy($id) sengaja, Model::destroy no-op kalau id gak ada jadi tetap 204 (bukan 404)
    public function destroy($id)
    {
        Produk::destroy($id);
        return response()->json(null, 204);
    }
}
