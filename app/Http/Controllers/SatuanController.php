<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index(Request $request)
    {
        // bisa ditambahkan pagination & search kalau perlu
        $perPage = $request->query('per_page', 15);
        $search  = $request->query('search');

        $query = Satuan::query();
        if ($search) {
            $query->where('nama_satuan', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_satuan'     => ['required','string','max:100','unique:satuans,nama_satuan'],
            'deskripsi'       => ['nullable','string'],
            'is_active'       => ['boolean'],
            'created_by'      => ['nullable','string','max:100'],
        ]);

        $data['created_time'] = now();

        $satuan = Satuan::create($data);

        return response()->json($satuan, 201);
    }

    public function show($id)
    {
        $satuan = Satuan::findOrFail($id);
        return response()->json($satuan);
    }

    public function update(Request $request, $id)
    {
        $satuan = Satuan::findOrFail($id);

        $data = $request->validate([
            'nama_satuan'     => ['required','string','max:100',"unique:satuans,nama_satuan,{$id},id_satuan"],
            'deskripsi'       => ['nullable','string'],
            'is_active'       => ['boolean'],
            'lastupdate_by'   => ['nullable','string','max:100'],
        ]);

        $data['lastupdate_time'] = now();

        $satuan->update($data);

        return response()->json($satuan);
    }

    public function destroy($id)
    {
        $satuan = Satuan::findOrFail($id);

    // kalau masih ada ukuran yang pakai satuan ini
    if ($satuan->ukurans()->exists()) {
        return response()->json([
            'message' => 'Tidak bisa menghapus: masih ada data Ukuran yang menggunakan Satuan ini.'
        ], 409);
    }

    $satuan->delete();
    return response()->json(null, 204);
    }
}
