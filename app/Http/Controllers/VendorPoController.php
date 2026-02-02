<?php

namespace App\Http\Controllers;

use App\Models\VendorPo;
use App\Models\Cabang;
use App\Models\Terminal;
use Illuminate\Http\Request;
use App\Models\Vendor;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

use PDF;


class VendorPoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $q = VendorPo::with(['vendor', 'terminal', 'produks.produk']);

        if ($search = $request->query('search')) {
            $q->where('nomor_po', 'like', "%{$search}%")
              ->orWhere('keterangan', 'like', "%{$search}%");
        }

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
{
    // Ambil id_cabang berdasarkan id_terminal (dari terminal)
    $terminal = Terminal::find($request->id_terminal);
    $vendor = Vendor::findOrFail($request->id_vendor); 
    $cabang = Cabang::find($terminal->id_cabang);

    // Ambil nomor PO terakhir dari tabel cabangs
    $lastNoPo = $cabang->urut_po;

    // Increment nomor urut (no_po)
    $newNoPo = (int)$lastNoPo + 1;

    // Mendapatkan bulan Romawi (misal: VIII untuk Agustus)
    $bulanRomawi = $this->getBulanRomawi(date('m')); 

    // Tahun (2 digit terakhir)
    $tahun = substr(date('Y'), -2);

    // Format nomor PO baru
    $nomorPo = str_pad($newNoPo, 3, '0', STR_PAD_LEFT)  . '/' . $vendor->inisial. '/' . $cabang->inisial_cabang. '/'  . $bulanRomawi . '/' . $tahun;

    // Menyimpan data Vendor PO
    $data = [
        'id_vendor' => $request->id_vendor,
        'id_terminal' => $request->id_terminal,
        'nomor_po' => $nomorPo,
        'tanggal_inven' => $request->tanggal_inven,
        'kd_tax' => $request->kd_tax,
        'terms' => $request->terms,
        'terms_day' => $request->terms_day,
        'subtotal' => $request->subtotal,
        'ppn11' => $request->ppn11,
        'total_order' => $request->total_order,
        'keterangan' => $request->keterangan,
        'terms_condition' => $request->terms_condition,
        'created_by' => $request->user()->name,
    ];

    // Simpan Vendor PO
    $vendorPo = VendorPo::create($data);

    // Update no_po di tabel cabangs
    $cabang->urut_po = $newNoPo;
    $cabang->save();

    return response()->json($vendorPo, 201);
}
    
    // Fungsi untuk mengubah bulan angka menjadi bulan Romawi
    private function getBulanRomawi($month)
    {
        $months = [
            '01' => 'I', '02' => 'II', '03' => 'III', '04' => 'IV', 
            '05' => 'V', '06' => 'VI', '07' => 'VII', '08' => 'VIII', 
            '09' => 'IX', '10' => 'X', '11' => 'XI', '12' => 'XII'
        ];
        return $months[$month] ?? 'I'; // Default to 'I' if not found
    }

    public function show($id)
    {
        $po = VendorPo::with(['vendor', 'terminal', 'produks.produk', 'produks.produk.ukuran.satuan', 'produks.produk.jenis'])
                      ->findOrFail($id);
    
        // Format nomor_po jika diperlukan
       // $po->nomor_po = $this->generateNomorPO($po);
    
        return response()->json($po);
    }

    private function generateNomorPO($po)
{
    // Example format: 001/SADP/SLW/IX/25
    $terminal = $po->terminal;
    $cabang = $po->cabang;

    // Format: 001/SADP/SLW/IX/25
    $bulanRomawi = $this->getBulanRomawi(now()->month);  // Convert month to Roman numeral
    $tahun = now()->year % 100;  // Get last 2 digits of the current year
    return sprintf("%03d/%s/%s/%s/%02d", $cabang->urut_po, $terminal->inisial_terminal, $cabang->inisial_cabang, $bulanRomawi, $tahun);
}

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_vendor'     => 'required|exists:vendors,id_vendor',
            'id_terminal'   => 'required|exists:terminals,id_terminal',
            'nomor_po'      => 'required|string|max:255',
            'tanggal_inven' => 'required|date',
            'kd_tax'        => 'required|string|max:10',
            'terms'         => 'required|string|max:10',
            'terms_day'     => 'required|integer',
            'subtotal'      => 'required|numeric',
            'ppn11'         => 'required|numeric',
            'total_order'   => 'required|numeric',
            'keterangan'    => 'nullable|string',
            'terms_condition'    => 'nullable|string',
            'disposisi_po'  => 'nullable|integer',
        ]);

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name;

        $po = VendorPo::findOrFail($id);
        $po->update($data);

        return response()->json($po);
    }

    public function approve(Request $request, $id)
{
    $po = VendorPo::findOrFail($id);
    $po->disposisi_po      = 1;
    $po->lastupdate_time   = now();
    $po->lastupdate_by     = $request->user()->name;
    $po->save();

    return response()->json($po);
}


// public function preview($id)
// {
//     $po = VendorPo::with(['vendor','terminal','produks.produk','produks.produk.ukuran.satuan', 'produks.produk.jenis'])->findOrFail($id);

//     // ambil dari public/images
//     $leftPath  = public_path('images/tds.png');
//     $rightPath = public_path('images/logo_prodiesel.png');

//     // ubah ke data URI (base64) — tahan banting untuk Dompdf
//     $logoLeft  = file_exists($leftPath)  ? 'data:image/png;base64,'.base64_encode(file_get_contents($leftPath))  : null;
//     $logoRight = file_exists($rightPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($rightPath)) : null;

//     return Pdf::loadView('vendorpos.preview', compact('po','logoLeft','logoRight'))
//         ->setPaper('a4','portrait')
//         ->setOptions([
//             'chroot'          => public_path(), // aman
//             'isRemoteEnabled' => true,
//         ])->stream("PO.pdf");
// }

public function preview($id)
{
    $po = VendorPo::with([
        'vendor',
        'terminal',
        'produks.produk',
        'produks.produk.ukuran.satuan',
        'produks.produk.jenis'
    ])->findOrFail($id);

    // Logo
    $leftPath  = public_path('images/tds.png');
    $rightPath = public_path('images/logo_prodiesel.png');

    $logoLeft  = file_exists($leftPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($leftPath))
        : null;

    $logoRight = file_exists($rightPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($rightPath))
        : null;

    /**
     * ==========================
     * QR SIGNATURE – DIREKTUR
     * ==========================
     */
    $qrPayload = json_encode([
        'type'      => 'APPROVAL_PO',
        'po_number' => $po->nomor_po,
        'approved'  => 'Vica Krisdianatha',
        'role'      => 'Direktur Utama',
        'date'      => now()->format('Y-m-d H:i:s'),
    ]);
    
    // $result = Builder::create()
    //     ->writer(new PngWriter())
    //     ->data($qrPayload)
    //     ->encoding(new Encoding('UTF-8'))
    //     ->errorCorrectionLevel(ErrorCorrectionLevel::High) // ⬅️ INI KUNCI-NYA
    //     ->size(220)
    //     ->margin(5)
    //     ->build();
    
    // $qrBase64 = 'data:image/png;base64,' . base64_encode($result->getString());
    
    $filename = 'PO-' . str_replace(['/', '\\'], '-', $po->nomor_po) . '.pdf';

    // return Pdf::loadView(
    //     'vendorpos.preview',
    //     compact('po', 'logoLeft', 'logoRight', 'qrBase64')
    // )

    return Pdf::loadView(
        'vendorpos.preview',
        compact('po', 'logoLeft', 'logoRight')
    )
    ->setPaper('a4', 'portrait')
    ->setOptions([
        'isRemoteEnabled' => true,
        'defaultFont' => 'DejaVu Sans',
    ])
    ->stream($filename);
    
}







/**
 * Endpoint publik untuk menampilkan data PO (JSON).
 */
public function publicShow($id)
{
    $po = VendorPo::with(['vendor','terminal','produks.produk'])
                  ->findOrFail($id);

    return response()->json($po);
}


    public function destroy($id)
    {
        VendorPo::destroy($id);
        return response()->json(null, 204);
    }

    
}
