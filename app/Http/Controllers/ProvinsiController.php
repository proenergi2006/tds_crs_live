<?php

namespace App\Http\Controllers;

use App\Models\Provinsi;
use Illuminate\Http\Request;

class ProvinsiController extends Controller
{
    public function index(Request $request)
    {
        $q = Provinsi::query();
        if ($s = $request->query('search')) {
            $q->where('nama_provinsi', 'like', "%{$s}%");
        }
        $perPage = $request->query('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    public function publicIndex(Request $request)
    {
        $q = Provinsi::query();

        if ($s = trim((string) $request->query('q', ''))) {
            // portable LIKE (PG/MySQL)
            $q->whereRaw('LOWER(nama_provinsi) LIKE ?', ['%'.strtolower($s).'%']);
        }

        $rows = $q->orderBy('nama_provinsi')
                  ->get(['id_provinsi as id', 'nama_provinsi as name']);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_provinsi' => 'required|string|max:255',
        ]);
        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;
        $provinsi = Provinsi::create($data);
        return response()->json($provinsi, 201);
    }

    // ** Inilah yang perlu ditambahkan **
    public function show($id)
    {
        $provinsi = Provinsi::findOrFail($id);
        return response()->json($provinsi);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nama_provinsi' => 'required|string|max:255',
        ]);
        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;
        $provinsi = Provinsi::findOrFail($id);
        $provinsi->update($data);
        return response()->json($provinsi);
    }

    public function destroy($id)
    {
        $provinsi = Provinsi::findOrFail($id);

        // Jika ada kabupaten terkait, tolak penghapusan
        if ($provinsi->kabupatens()->exists()) {
            return response()->json([
                'message' => 'Provinsi ini tidak dapat dihapus karena masih ada kabupaten yang terkait.'
            ], 409);
        }

        $provinsi->delete();

        return response()->json(null, 204);

    }
}
