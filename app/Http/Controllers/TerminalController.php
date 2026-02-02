<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    public function index(Request $request)
    {
        $q = Terminal::with('cabang');
        if ($s = $request->query('search')) {
            $q->where('nama_terminal', 'like', "%{$s}%")
              ->orWhere('kategori_terminal', 'like', "%{$s}%");
        }
        $perPage = $request->query('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_terminal'      => 'required|string|max:255',
            'id_cabang'          => 'required|exists:cabangs,id_cabang',
            'kategori_terminal'  => 'required|string|max:100',
            'inisial'            => 'nullable|string|max:50',
            'lokasi'             => 'nullable|string|max:255',
            'telp_terminal'      => 'nullable|string|max:30',
            'alamat'             => 'nullable|string',
            'fax'                => 'nullable|string|max:30',
            'pic'                => 'nullable|string|max:100',
        ]);
        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name;
        $terminal = Terminal::create($data);
        return response()->json($terminal, 201);
    }

    public function show($id)
    {
        $terminal = Terminal::with('cabang')->findOrFail($id);
        return response()->json($terminal);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nama_terminal'      => 'required|string|max:255',
            'id_cabang'          => 'required|exists:cabangs,id_cabang',
            'kategori_terminal'  => 'required|string|max:100',
            'inisial'            => 'nullable|string|max:50',
            'lokasi'             => 'nullable|string|max:255',
            'telp_terminal'      => 'nullable|string|max:30',
            'alamat'             => 'nullable|string',
            'fax'                => 'nullable|string|max:30',
            'pic'                => 'nullable|string|max:100',
        ]);
        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;
        $terminal = Terminal::findOrFail($id);
        $terminal->update($data);
        return response()->json($terminal);
    }

    public function destroy($id)
    {
        Terminal::destroy($id);
        return response()->json(null, 204);
    }
}
