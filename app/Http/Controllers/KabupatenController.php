<?php

namespace App\Http\Controllers;

use App\Models\Kabupaten;
use Illuminate\Http\Request;

class KabupatenController extends Controller
{
    /**
     * Tampilkan daftar kabupaten, dengan pagination dan search.
     */
    public function index(Request $request)
    {
        $q = Kabupaten::with('provinsi');

    // pakai param "id_provinsi" sesuai nama kolom di DB
    if ($provId = $request->query('id_provinsi')) {
        $q->where('id_provinsi', $provId);
    }

    if ($s = $request->query('search')) {
        $q->where('nama_kabupaten', 'like', "%{$s}%");
    }

    $perPage = $request->query('per_page', 10);
    return response()->json($q->paginate($perPage));
    }

    public function publicIndex(Request $request)
    {
        $q = Kabupaten::query();

        if ($provId = $request->query('provinsi_id')) {
            $q->where('id_provinsi', $provId);
        }
        if ($s = trim((string) $request->query('q', ''))) {
            $q->whereRaw('LOWER(nama_kabupaten) LIKE ?', ['%'.strtolower($s).'%']);
        }

        $rows = $q->orderBy('nama_kabupaten')
                  ->limit(500)
                  ->get(['id_kabupaten as id', 'nama_kabupaten as name', 'id_provinsi']);

        return response()->json(['data' => $rows]);
    }

    /**
     * Simpan kabupaten baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_provinsi'    => 'required|exists:provinsis,id_provinsi',
            'nama_kabupaten' => 'required|string|max:255',
        ]);

        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;

        $kabupaten = Kabupaten::create($data);

        return response()->json($kabupaten, 201);
    }

    /**
     * Tampilkan detail satu kabupaten (untuk edit).
     */
    public function show($id)
    {
        // Pastikan load juga relasi provinsi
        $kabupaten = Kabupaten::with('provinsi')->findOrFail($id);
        return response()->json($kabupaten);
    }

    /**
     * Update kabupaten.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_provinsi'    => 'required|exists:provinsis,id_provinsi',
            'nama_kabupaten' => 'required|string|max:255',
        ]);

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;

        $kabupaten = Kabupaten::findOrFail($id);
        $kabupaten->update($data);

        return response()->json($kabupaten);
    }

    /**
     * Hapus kabupaten.
     */
    public function destroy($id)
    {
        Kabupaten::destroy($id);
        return response()->json(null, 204);
    }
}
