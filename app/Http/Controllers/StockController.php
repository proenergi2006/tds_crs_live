<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * GET /api/stocks
     * Mengembalikan ringkasan stok per produk.
     */
    
     public function index(Request $request)
     {
         $stocks = Stock::with([
                 'poProduk.vendorPo',
                 'poProduk.produk.jenis',
                 'poProduk.produk.ukuran.satuan',
             ])
             ->orderBy('created_at', 'desc')
             ->get()
             ->map(function($row) {
                 $produk  = $row->poProduk->produk;
                 $ukuran  = $produk->ukuran;
                 $satuan  = $ukuran?->satuan;
                 $jenis   = $produk->jenis;
     
                 return [
                     'id'              => $row->id,
                     'po_produk_id'    => $row->po_produk_id,
                     'receive_item_id' => $row->receive_item_id,
                     'produk_id'       => $produk->id_produk,
                     'produk_label'    => "{$produk->nama_produk} - " .
                                          ($jenis->nama ?? '-') . " (" .
                                          ($ukuran->nama_ukuran ?? '-') . " " .
                                          ($satuan->nama_satuan ?? '-') . ")",
                     'nomor_po'        => $row->poProduk->vendorPo->nomor_po,
                     'volume'          => (float) $row->volume,
                     'harga_tebus'     => (float) $row->harga_tebus,
                     'created_at'      => $row->created_at->toDateTimeString(),
                 ];
             });
     
         return response()->json($stocks);
     }
    }