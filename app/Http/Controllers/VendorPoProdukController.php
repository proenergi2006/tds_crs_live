<?php

namespace App\Http\Controllers;

use App\Models\VendorPoProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPoProdukController extends Controller
{
    // GET /api/vendor-pos-produks
    public function index(Request $request)
    {
        $q = VendorPoProduk::with(['vendorPo', 'produk']);

        if ($poId = $request->query('id_po')) {
            $q->where('id_po', $poId);
        }

        return response()->json($q->get());
    }

    // POST /api/vendor-pos-produks
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_po'         => 'required|exists:vendor_pos,id_po',
            'id_produk'     => 'required|exists:produks,id_produk',
            'volume_po'     => 'required|numeric',
            'harga_tebus'   => 'required|numeric',
            'jumlah_harga'  => 'required|numeric',
        ]);

        $data['created_time'] = now();

        $detail = VendorPoProduk::create($data);

        return response()->json($detail, 201);
    }

    // POST /api/vendor-pos-produks/batch
    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.id_po'        => 'required|exists:vendor_pos,id_po',
            'items.*.id_produk'    => 'required|exists:produks,id_produk',
            'items.*.volume_po'    => 'required|numeric',
            'items.*.harga_tebus'  => 'required|numeric',
            'items.*.jumlah_harga' => 'required|numeric',
        ]);

        DB::transaction(function() use ($data) {
            foreach ($data['items'] as $row) {
                $row['created_time'] = now();
                VendorPoProduk::create($row);
            }
        });

        return response()->json(['message' => 'Detail PO berhasil disimpan'], 201);
    }

    // GET /api/vendor-pos-produks/{id}
    public function show($id)
    {
        $detail = VendorPoProduk::with(['vendorPo', 'produk'])
                    ->findOrFail($id);

        return response()->json($detail);
    }

    // PUT /api/vendor-pos-produks/{id}
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'volume_po'     => 'required|numeric',
            'harga_tebus'   => 'required|numeric',
            'jumlah_harga'  => 'required|numeric',
        ]);

        $data['lastupdate_time'] = now();

        $detail = VendorPoProduk::findOrFail($id);
        $detail->update($data);

        return response()->json($detail);
    }

    // DELETE /api/vendor-pos-produks/{id}
    public function destroy($id)
    {
        VendorPoProduk::destroy($id);
        return response()->json(null, 204);
    }

    public function destroyByPo(Request $request)
    {
        $poId = $request->query('id_po');
        if (! $poId) {
            return response()->json(['message'=>'id_po is required'], 422);
        }
        \App\Models\VendorPoProduk::where('id_po', $poId)->delete();
        return response()->json(null, 204);
    }
}
