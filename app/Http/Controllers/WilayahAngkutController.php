<?php 
namespace App\Http\Controllers;

use App\Models\WilayahAngkut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WilayahAngkutController extends Controller
{
    public function index(Request $request)
    {
        // 'province'/'regency' ditambahkan di samping 'provinsi'/'kabupaten'
        // lama (laravel-nusa-address-full-migration Task 7) — lama tetap
        // dieager-load, tidak dihapus. Tabel ini tidak punya sumber
        // kecamatan/kelurahan, jadi 'district'/'village' biasanya null
        // tapi tetap dieager-load untuk konsistensi.
        $data = WilayahAngkut::with(['provinsi', 'kabupaten', 'province', 'regency', 'district', 'village'])
            ->latest()
            ->paginate($request->get('per_page', 100));

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // id_provinsi/id_kabupaten dilonggarkan jadi nullable (Task 8.1,
            // opsi a) — tabel referensi lama provinsis/kabupatens (13/25
            // baris) tidak lengkap dibanding data BPS province_id/regency_id
            // di bawah (38/514 baris).
            'id_provinsi' => 'nullable|exists:provinsis,id_provinsi',
            'id_kabupaten' => 'nullable|exists:kabupatens,id_kabupaten',
            // Kolom baru berbasis kode BPS.
            'province_id' => 'nullable|string|exists:provinces,id',
            'regency_id' => 'nullable|string|exists:regencies,id',
            'district_id' => 'nullable|string|exists:districts,id',
            'village_id' => 'nullable|string|exists:villages,id',
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
        $item = WilayahAngkut::with(['provinsi', 'kabupaten', 'province', 'regency', 'district', 'village'])->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = WilayahAngkut::findOrFail($id);

        $data = $request->validate([
            // id_provinsi/id_kabupaten dilonggarkan jadi nullable (Task 8.1,
            // opsi a) — lihat catatan di store() di atas.
            'id_provinsi' => 'nullable|exists:provinsis,id_provinsi',
            'id_kabupaten' => 'nullable|exists:kabupatens,id_kabupaten',
            'province_id' => 'nullable|string|exists:provinces,id',
            'regency_id' => 'nullable|string|exists:regencies,id',
            'district_id' => 'nullable|string|exists:districts,id',
            'village_id' => 'nullable|string|exists:villages,id',
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
