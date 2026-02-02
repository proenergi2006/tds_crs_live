<?php

namespace App\Http\Controllers;

use App\Models\MasterTruck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MasterTruckController extends Controller
{
    public function index(Request $request)
    {
        $data = MasterTruck::with('transportir')
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nopol' => 'required|string|unique:master_trucks,nopol',
            'jenis_truck' => 'required|string|max:255',
            'kapasitas' => 'required|numeric|min:0',
            'id_transportir' => 'required|exists:transportirs,id',
            'dokumen' => 'nullable|string|max:255',
            'masa_berlaku' => 'nullable|date',
            'lampiran' => 'nullable|file|mimes:pdf|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store('lampiran/truck', 'public');
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $truck = MasterTruck::create($data);

        return response()->json($truck, 201);
    }

    public function show($id)
    {
        $truck = MasterTruck::with('transportir')->findOrFail($id);
        return response()->json($truck);
    }

    public function update(Request $request, $id)
    {
        $truck = MasterTruck::findOrFail($id);

        $data = $request->validate([
            'nopol' => 'required|string|unique:master_trucks,nopol,' . $id,
            'jenis_truck' => 'required|string|max:255',
            'kapasitas' => 'required|numeric|min:0',
            'id_transportir' => 'required|exists:transportirs,id',
            'dokumen' => 'nullable|string|max:255',
            'masa_berlaku' => 'nullable|date',
            'lampiran' => 'nullable|file|mimes:pdf|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('lampiran')) {
            // hapus lampiran lama jika ada
            if ($truck->lampiran) {
                Storage::disk('public')->delete($truck->lampiran);
            }
            $data['lampiran'] = $request->file('lampiran')->store('lampiran/truck', 'public');
        }

        $data['updated_by'] = Auth::id();
        $truck->update($data);

        return response()->json($truck);
    }

    public function destroy($id)
    {
        $truck = MasterTruck::findOrFail($id);

        if ($truck->lampiran) {
            Storage::disk('public')->delete($truck->lampiran);
        }

        $truck->delete();

        return response()->json(['message' => 'Data truck berhasil dihapus']);
    }
}

