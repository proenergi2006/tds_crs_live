<?php 
namespace App\Http\Controllers;

use App\Models\WilayahAngkut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WilayahAngkutController extends Controller
{
    public function index(Request $request)
    {
        $data = WilayahAngkut::with(['provinsi', 'kabupaten'])
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_provinsi' => 'required|exists:provinsis,id_provinsi',
            'id_kabupaten' => 'required|exists:kabupatens,id_kabupaten',
            'destinasi' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $item = WilayahAngkut::create($data);
        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = WilayahAngkut::with(['provinsi', 'kabupaten'])->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = WilayahAngkut::findOrFail($id);

        $data = $request->validate([
            'id_provinsi' => 'required|exists:provinsis,id_provinsi',
            'id_kabupaten' => 'required|exists:kabupatens,id_kabupaten',
            'destinasi' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['updated_by'] = Auth::id();
        $item->update($data);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = WilayahAngkut::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
