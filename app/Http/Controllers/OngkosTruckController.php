<?php

namespace App\Http\Controllers;

use App\Models\OngkosTruck;
use App\Models\OngkosTruckDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OngkosTruckController extends Controller
{
    public function index(Request $request)
    {
        $data = OngkosTruck::with(['transportir', 'angkutWilayah', 'details.volume'])
            ->latest()
            ->paginate($request->get('per_page', 100));

        return response()->json($data);
    }

    public function checkOA(Request $request)
{
    $request->validate([
        'id_transportir' => 'required|numeric',
        'id_angkut_wilayah' => 'required|numeric',
        'id_volume' => 'required|numeric',
    ]);

    $ongkos = OngkosTruck::where('id_transportir', $request->id_transportir)
        ->where('id_angkut_wilayah', $request->id_angkut_wilayah)
        ->first();

    if (!$ongkos) return response()->json(['oa' => 0]);

    $detail = $ongkos->details()->where('id_volume', $request->id_volume)->first();

    return response()->json(['oa' => $detail?->oa ?? 0]);
}


    public function store(Request $request)
    {
        $data = $request->validate([
            'id_transportir' => 'required|exists:transportirs,id',
            'id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
            'details' => 'required|array|min:1',
            'details.*.id_volume' => 'required|exists:volumes,id_volume',
            'details.*.oa' => 'required|numeric|min:0',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $ongkosTruck = OngkosTruck::create($data);

        foreach ($data['details'] as $detail) {
            $ongkosTruck->details()->create($detail);
        }

        return response()->json($ongkosTruck->load(['details.volume']), 201);
    }

    public function show($id)
    {
        $item = OngkosTruck::with(['details.volume'])->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $ongkosTruck = OngkosTruck::findOrFail($id);

        $data = $request->validate([
            'id_transportir' => 'required|exists:transportirs,id',
            'id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
            'details' => 'required|array|min:1',
            'details.*.id_volume' => 'required|exists:volumes,id_volume',
            'details.*.oa' => 'required|numeric|min:0',
        ]);

        $data['updated_by'] = Auth::id();

        $ongkosTruck->update($data);
        $ongkosTruck->details()->delete();

        foreach ($data['details'] as $detail) {
            $ongkosTruck->details()->create($detail);
        }

        return response()->json($ongkosTruck->load(['details.volume']));
    }

    public function destroy($id)
    {
        $ongkosTruck = OngkosTruck::findOrFail($id);
        $ongkosTruck->details()->delete();
        $ongkosTruck->delete();

        return response()->json(['message' => 'Data ongkos truck berhasil dihapus']);
    }
}
