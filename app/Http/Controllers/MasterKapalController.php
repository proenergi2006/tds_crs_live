<?php

namespace App\Http\Controllers;

use App\Models\MasterKapal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterKapalController extends Controller
{
    public function index(Request $request)
    {
        return MasterKapal::with('transportir')
            ->latest()
            ->paginate($request->get('per_page', 100));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kapal'     => 'required|string|max:255',
            'kapasitas_max'  => 'nullable|integer',
            'kelas'          => 'nullable|string',
            'panjang'        => 'nullable|numeric',
            'lebar'          => 'nullable|numeric',
            'asal_kapal'     => 'nullable|string',
            'tipe_kapal'     => 'nullable|string',
            'id_transportir' => 'required|exists:transportirs,id',
            'dokumen'        => 'nullable|string',
            'masa_berlaku'   => 'nullable|date',
           'lampiran' => 'nullable|file|mimes:pdf|max:2048',

            'is_active'      => 'boolean',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return MasterKapal::create($data);
    }

    public function show($id)
    {
        return MasterKapal::with('transportir')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kapal = MasterKapal::findOrFail($id);

        $data = $request->validate([
            'nama_kapal'     => 'required|string|max:255',
            'kapasitas_max'  => 'nullable|integer',
            'kelas'          => 'nullable|string',
            'panjang'        => 'nullable|numeric',
            'lebar'          => 'nullable|numeric',
            'asal_kapal'     => 'nullable|string',
            'tipe_kapal'     => 'nullable|string',
            'id_transportir' => 'required|exists:transportirs,id',
            'dokumen'        => 'nullable|string',
            'masa_berlaku'   => 'nullable|date',
       'lampiran' => 'nullable|file|mimes:pdf|max:2048',

            'is_active'      => 'boolean',
        ]);

        $data['updated_by'] = Auth::id();
        $kapal->update($data);

        return $kapal;
    }

    public function destroy($id)
    {
        MasterKapal::findOrFail($id)->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
