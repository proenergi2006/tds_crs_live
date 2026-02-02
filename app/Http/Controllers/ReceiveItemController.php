<?php

namespace App\Http\Controllers;

use App\Models\ReceiveItem;
use App\Models\ReceiveItemProduk;
use App\Models\VendorPo;
use Illuminate\Http\Request;
use App\Models\Stock; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReceiveItemController extends Controller
{
    public function index(Request $request, $poId)
    {
        $po = VendorPo::findOrFail($poId);

        // Eager‐load details → produk (langsung), tanpa perlu poProduk.produk
        $receives = ReceiveItem::with('details.produk')
            ->where('po_id', $poId)
            ->orderBy('received_at', 'desc')
            ->get()
            ->map(function ($receive) {
                if ($receive->file_path) {
                    $receive->file_url = Storage::disk('public')->url($receive->file_path);
                } else {
                    $receive->file_url = null;
                }
                $receive->total_volume_terima = $receive->details->sum('volume_terima');
                return $receive;
            });

        return response()->json($receives);
    }

    public function store(Request $request, $poId)
    {
        $po = VendorPo::findOrFail($poId);

        $validator = Validator::make($request->all(), [
            'received_at'             => 'required|date',
            'nama_pic'                => 'required|string|max:255',
            'file'                    => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'details'                 => 'required|array',
            'details.*.volume_bl'     => 'required|numeric|min:0',
            'details.*.volume_terima' => 'required|numeric|min:0',
            'details.*.selisih'       => 'required|numeric', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $receive = new ReceiveItem();
        $receive->po_id       = $poId;
        $receive->received_at = $data['received_at'];
        $receive->nama_pic    = $data['nama_pic'];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('receive_files', 'public');
            $receive->file_path = $path;
        }
        $receive->save();

        foreach ($data['details'] as $poProdukId => $detail) {
            $poProduk = $po->produks()->where('id_po_produk', $poProdukId)->first();
            if (! $poProduk) {
                continue; // abaikan jika id_po_produk invalid
            }

            $item = new ReceiveItemProduk();
            $item->receive_item_id = $receive->id;
            $item->po_produk_id    = (int) $poProdukId;
            $item->id_produk       = $poProduk->id_produk;
            $item->harga_tebus     = $poProduk->harga_tebus;
            $item->volume_bl       = $detail['volume_bl'];
            $item->volume_terima   = $detail['volume_terima'];
            $item->selisih         = $detail['selisih'];  // ⟵ ambil dari front‐end
            $item->save();
     

        Stock::create([
            'po_produk_id'    => $item->po_produk_id,
            'receive_item_id' => $receive->id,
            'produk_id'       => $item->id_produk,
            'volume'          => $item->volume_terima,
            'harga_tebus'     => $item->harga_tebus,
        ]);
    }

        $receive->load('details.produk');
        return response()->json($receive, 201);
    }
}
