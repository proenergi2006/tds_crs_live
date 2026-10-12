<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
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

        if ($request->boolean('as_list')) {
            return response()->json($query->orderBy('nama')->get());
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

    public function show(JenisProduk $jenisProduk)
    {
        return response()->json($jenisProduk);
    }

    public function update(Request $request, JenisProduk $jenisProduk)
    {
        $validator = Validator::make($request->all(), [
            'nama'      => 'required|string|max:255|unique:jenis_produks,nama,' . $jenisProduk->id_jenis . ',id_jenis',
            'deskripsi' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = $request->user()->name ?? null;
        $jenisProduk->update($data);

        return response()->json($jenisProduk);
    }

    public function destroy(JenisProduk $jenisProduk)
    {
        $jenisProduk->delete();
        return response()->json(null, 204);
    }
}
