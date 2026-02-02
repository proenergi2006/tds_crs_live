<?php

namespace App\Http\Controllers;

use App\Models\Volume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolumeController extends Controller
{
    public function index(Request $request)
    {
        $query = Volume::with('satuan');

        if ($request->has('search')) {
            $query->where('volume', 'like', '%' . $request->search . '%');
        }

        return response()->json(
            $query->latest()->paginate($request->get('per_page', 10))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'volume'     => 'required|numeric|min:0',
            'id_satuan'  => 'required|exists:satuans,id_satuan',
            'is_active'  => 'required|boolean',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $volume = Volume::create($data);

        return response()->json($volume, 201);
    }

    public function show($id)
    {
        $volume = Volume::with('satuan')->findOrFail($id);
        return response()->json($volume);
    }

    public function update(Request $request, $id)
    {
        $volume = Volume::findOrFail($id);

        $data = $request->validate([
            'volume'     => 'required|numeric|min:0',
            'id_satuan'  => 'required|exists:satuans,id_satuan',
            'is_active'  => 'required|boolean',
        ]);

        $data['updated_by'] = Auth::id();

        $volume->update($data);

        return response()->json($volume);
    }

    public function destroy($id)
    {
        $volume = Volume::findOrFail($id);
        $volume->delete();

        return response()->json(['message' => 'Data volume berhasil dihapus.']);
    }
}
