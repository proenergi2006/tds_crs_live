<?php
namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $query = Cabang::query();

        if ($search = $request->query('search')) {
            $query->where('nama_cabang', 'like', "%{$search}%");
        }

        $data = $query->paginate($perPage);
        return response()->json($data);
    }

    public function suggest(Request $request)
{
    $q     = $request->query('q');
    $limit = (int) $request->query('limit', 8);

    $query = Cabang::query()
        ->select('id_cabang', 'nama_cabang', 'inisial_cabang', 'inisial_segel', 'is_active');

    if ($q) {
        $query->where(function ($w) use ($q) {
            $w->where('nama_cabang', 'like', "%{$q}%")
              ->orWhere('inisial_cabang', 'like', "%{$q}%")
              ->orWhere('inisial_segel', 'like', "%{$q}%");
        });
    }

    $list = $query->orderBy('nama_cabang')->limit($limit)->get();

    return response()->json($list);
}


    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_cabang'     => 'required|string|max:150|unique:cabangs,nama_cabang',
            'is_active'       => 'boolean',
            'created_by'      => 'nullable|string',
            'inisial_cabang'  => 'nullable|string|max:10',   // Validasi untuk inisial cabang
            'inisial_segel'   => 'nullable|string|max:10',   // Validasi untuk inisial segel
            'catatan_cabang'  => 'nullable|string|max:500',  // Validasi untuk catatan cabang
        ]);

        $data['created_time'] = now();
        $cabang = Cabang::create($data);

        return response()->json($cabang, 201);
    }

    public function show($id)
    {
        $cabang = Cabang::findOrFail($id);
        return response()->json($cabang);
    }

    public function update(Request $request, $id)
    {
        $cabang = Cabang::findOrFail($id);

        $data = $request->validate([
            'nama_cabang'     => "required|string|max:150|unique:cabangs,nama_cabang,{$id},id_cabang",
            'is_active'       => 'boolean',
            'lastupdate_by'   => 'nullable|string',
            'inisial_cabang'  => 'nullable|string|max:10',   // Validasi untuk inisial cabang
            'inisial_segel'   => 'nullable|string|max:10',   // Validasi untuk inisial segel
            'catatan_cabang'  => 'nullable|string|max:500',  // Validasi untuk catatan cabang
        ]);

        $data['lastupdate_time'] = now();
        $cabang->update($data);

        return response()->json($cabang);
    }

    public function destroy($id)
    {
        Cabang::destroy($id);
        return response()->json(null, 204);
    }
}
