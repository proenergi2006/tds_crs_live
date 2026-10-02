<?php

namespace App\Http\Controllers\MasterLogistik;

use App\Http\Controllers\Controller;
use App\Models\Volume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolumeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('logistik.master.manage') && $user->cant('logistik.master.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Volume::with('satuan')->orderBy('volume');

        if ($request->has('search')) {
            $query->where('volume', 'like', '%'.$request->search.'%');
        }

        if ($request->boolean('as_list')) {
            return response()->json(['data' => $query->get()]);
        }

        return response()->json($query->paginate($request->get('per_page', 10)));
    }

    public function store(Request $request)
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'volume' => 'required|numeric|min:0',
            'id_satuan' => 'required|exists:satuans,id_satuan',
            'is_active' => 'required|boolean',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $volume = Volume::create($data);

        return response()->json($volume, 201);
    }

    public function show(Request $request, $id)
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $volume = Volume::with('satuan')->findOrFail($id);

        return response()->json($volume);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $volume = Volume::findOrFail($id);

        $data = $request->validate([
            'volume' => 'required|numeric|min:0',
            'id_satuan' => 'required|exists:satuans,id_satuan',
            'is_active' => 'required|boolean',
        ]);

        $data['updated_by'] = Auth::id();

        $volume->update($data);

        return response()->json($volume);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->cant('logistik.master.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $volume = Volume::findOrFail($id);
        $volume->delete();

        return response()->json(['message' => 'Data volume berhasil dihapus.']);
    }
}
