<?php

namespace App\Http\Controllers;

use App\Models\VendorPoProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPoProdukController extends Controller
{
    public function index(Request $request)
    {
        $q = VendorPoProduk::with(['vendorPo', 'produk']);

        if ($poId = $request->query('id_po')) {
            $q->where('id_po', $poId);
        }

        return response()->json($q->get());
    }

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

    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.id_po'          => 'required|exists:vendor_pos,id_po',
            'items.*.id_produk'      => 'required|exists:produks,id_produk',
            'items.*.volume_po'      => 'required|numeric',
            'items.*.harga_tebus'    => 'required|numeric',
            'items.*.jumlah_harga'   => 'required|numeric',
            'items.*.kd_tax'         => 'nullable|string|in:E,EC',
            'items.*.tax_amount'     => 'nullable|numeric',
        ]);

        DB::transaction(function() use ($data) {
            foreach ($data['items'] as $row) {
                $row['created_time'] = now();
                VendorPoProduk::create($row);
            }
        });

        return response()->json(['message' => 'Detail PO berhasil disimpan'], 201);
    }

    public function show(VendorPoProduk $vendorPosProduk)
    {
        $detail = $vendorPosProduk->load(['vendorPo', 'produk']);

        return response()->json($detail);
    }

    public function update(Request $request, VendorPoProduk $vendorPosProduk)
    {
        $data = $request->validate([
            'volume_po'     => 'required|numeric',
            'harga_tebus'   => 'required|numeric',
            'jumlah_harga'  => 'required|numeric',
        ]);

        $data['lastupdate_time'] = now();

        $detail = $vendorPosProduk;
        $detail->update($data);

        return response()->json($detail);
    }

    // destroy($id) sengaja, Model::destroy no-op kalau id gak ada jadi tetap 204 (bukan 404)
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
