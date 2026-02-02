<?php

namespace App\Http\Controllers;

use App\Models\Transportir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransportirController extends Controller
{
    public function index(Request $request)
    {
        $query = Transportir::with('cabang');

        if ($request->has('search')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->search . '%');
        }

        return response()->json(
            $query->latest()->paginate($request->get('per_page', 10))
        );
    }

    public function show($id)
    {
        $data = Transportir::with('cabang')->findOrFail($id);
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store('lampiran/transportir', 'public');
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $transportir = Transportir::create($data);
        return response()->json($transportir, 201);
    }

    public function update(Request $request, $id)
    {
        $transportir = Transportir::findOrFail($id);
        $data = $this->validateData($request);

        if ($request->hasFile('lampiran')) {
            if ($transportir->lampiran) {
                Storage::disk('public')->delete($transportir->lampiran);
            }
            $data['lampiran'] = $request->file('lampiran')->store('lampiran/transportir', 'public');
        }

        $data['updated_by'] = Auth::id();

        $transportir->update($data);
        return response()->json($transportir);
    }

    public function destroy($id)
    {
        $transportir = Transportir::findOrFail($id);

        if ($transportir->lampiran) {
            Storage::disk('public')->delete($transportir->lampiran);
        }

        $transportir->delete();
        return response()->json(['message' => 'Data transportir berhasil dihapus.']);
    }

    private function validateData(Request $request)
    {
        return $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'singkatan'       => 'required|string|max:50',
            'kepemilikan'     => 'required|string|max:100',
            'id_cabang'       => 'required|exists:cabangs,id_cabang',
            'telpon'          => 'required|string|max:50',
            'fax'             => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'nomor_hp'        => 'nullable|string|max:50',
            'angkutan_kirim'  => 'required|string|max:255',
            'terms'           => 'nullable|string|max:255',
            'alamat'          => 'nullable|string|max:500',
            'perizinan'       => 'nullable|string|max:255',
            'masa_berlaku'    => 'nullable|date',
            'is_active'       => 'required|in:1,0',
            'fleet'           => 'nullable|in:1,0',
            'catatan'         => 'nullable|string',
            'lampiran'        => 'nullable|file|mimes:pdf|max:2048'
        ]);
    }
}
