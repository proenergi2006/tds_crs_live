<?php

namespace App\Http\Controllers;

use App\Models\JenisProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JenisProdukController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = JenisProduk::query();
        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        return response()->json($query->orderBy('nama')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'      => 'required|string|max:255|unique:jenis_produks,nama',
            'deskripsi' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = $request->user()->name ?? null;
        $jenis = JenisProduk::create($data);

        return response()->json($jenis, 201);
    }

    public function show($id)
    {
        $jenis = JenisProduk::findOrFail($id);
        return response()->json($jenis);
    }

    public function update(Request $request, $id)
    {
        $jenis = JenisProduk::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'nama'      => 'required|string|max:255|unique:jenis_produks,nama,' . $id . ',id_jenis',
            'deskripsi' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = $request->user()->name ?? null;
        $jenis->update($data);

        return response()->json($jenis);
    }

    public function destroy($id)
    {
        $jenis = JenisProduk::findOrFail($id);
        $jenis->delete();
        return response()->json(null, 204);
    }
}
