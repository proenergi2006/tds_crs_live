<?php

namespace App\Http\Controllers;

use App\Models\Ukuran;
use Illuminate\Http\Request;

class UkuranController extends Controller
{
    public function index(Request $request)
    {
        $q = Ukuran::with('satuan');
        if ($s = $request->query('search')) {
            $q->where('nama_ukuran', 'like', "%{$s}%");
        }
        $perPage = $request->query('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_ukuran'      => 'required|string',
            'id_satuan'        => 'required|exists:satuans,id_satuan',
        ]);
        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;
        $ukuran = Ukuran::create($data);
        // eager-load relasi 'satuan' sebelum return
    return response()->json(
        $ukuran->load('satuan'),
        201
    );
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nama_ukuran'      => 'required|string',
            'id_satuan'        => 'required|exists:satuans,id_satuan',
        ]);
        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;
        $ukuran = Ukuran::findOrFail($id);
        $ukuran->update($data);
       // fresh() + load relasi untuk memastikan data terbaru
    return response()->json(
        $ukuran->fresh('satuan')
    );
    }

    public function destroy($id)
    {
        Ukuran::destroy($id);
        return response()->json(null, 204);
    }
}
