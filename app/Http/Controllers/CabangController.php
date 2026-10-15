<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cabang::class);

        $query = Cabang::query();

        if ($request->boolean('as_list')) {
            return response()->json(
                $query->select('id_cabang', 'nama_cabang')
                    ->orderBy('nama_cabang')
                    ->get()
            );
        }

        if ($search = $request->query('search')) {
            $query->where('nama_cabang', 'like', "%{$search}%");
        }

        $perPage = $request->query('per_page', 10);
        $data = $query->paginate($perPage);
        return response()->json($data);
    }

    public function suggest(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cabang::class);

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


    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Cabang::class);

        $data = $request->validate([
            'nama_cabang'     => 'required|string|max:150|unique:cabangs,nama_cabang',
            'is_active'       => 'boolean',
            'created_by'      => 'nullable|string',
            'inisial_cabang'  => 'nullable|string|max:10',
            'inisial_segel'   => 'nullable|string|max:10',
            'catatan_cabang'  => 'nullable|string|max:500',
        ]);

        $data['created_time'] = now();
        $cabang = Cabang::create($data);

        return response()->json($cabang, 201);
    }

    public function show(Cabang $cabang): JsonResponse
    {
        $this->authorize('view', $cabang);

        return response()->json($cabang);
    }

    public function update(Request $request, Cabang $cabang): JsonResponse
    {
        $this->authorize('update', $cabang);

        $data = $request->validate([
            'nama_cabang'     => "required|string|max:150|unique:cabangs,nama_cabang,{$cabang->id_cabang},id_cabang",
            'is_active'       => 'boolean',
            'lastupdate_by'   => 'nullable|string',
            'inisial_cabang'  => 'nullable|string|max:10',
            'inisial_segel'   => 'nullable|string|max:10',
            'catatan_cabang'  => 'nullable|string|max:500',
        ]);

        $data['lastupdate_time'] = now();
        $cabang->update($data);

        return response()->json($cabang);
    }

    // NOTE: pengecualian route model binding kategori 6 — Model::destroy($id) sengaja dipertahankan supaya id yang sudah tidak ada tetap 204 (bukan 404).
    public function destroy($id): JsonResponse
    {
        $this->authorize('delete', Cabang::class);

        Cabang::destroy($id);
        return response()->json(null, 204);
    }
}
