<?php

namespace App\Http\Controllers;

use App\Models\VendorPo;
use App\Models\Cabang;
use App\Models\Terminal;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorPoProduk;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\DB;
use PDF;


class VendorPoController extends Controller
{
    public function index(Request $request)
    {
        $q = VendorPo::with(['vendor', 'terminal']);

        if ($search = $request->query('search')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('nomor_po', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($tanggalDari = $request->query('tanggal_dari')) {
            $q->whereDate('tanggal_inven', '>=', $tanggalDari);
        }

        if ($tanggalSampai = $request->query('tanggal_sampai')) {
            $q->whereDate('tanggal_inven', '<=', $tanggalSampai);
        }

        if ($terminal = $request->query('id_terminal')) {
            $q->where('id_terminal', $terminal);
        }

        if ($vendor = $request->query('id_vendor')) {
            $q->where('id_vendor', $vendor);
        }

        $q->orderByDesc('tanggal_inven')->orderByDesc('id_po');

        return response()->json(
            $q->paginate($request->query('per_page', 10))
        );
    }

    public function show($id)
    {
        $po = VendorPo::with(['vendor', 'terminal', 'produks.produk', 'produks.produk.ukuran.satuan', 'produks.produk.jenis'])
            ->findOrFail($id);

        return response()->json($po);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_vendor'         => 'required|exists:vendors,id_vendor',
            'id_terminal'       => 'required|exists:terminals,id_terminal',
            'tanggal_inven'     => 'required|date',
            'terms'             => 'nullable|string',
            'terms_day'         => 'nullable|numeric',
            'subtotal'          => 'required|numeric',
            'ppn11'             => 'required|numeric',
            'total_order'       => 'required|numeric',
            'keterangan'        => 'nullable|string',
            'terms_condition'   => 'nullable|string',

            'items'                     => 'required|array|min:1',
            'items.*.id_produk'         => 'required|exists:produks,id_produk',
            'items.*.volume_po'         => 'required|numeric',
            'items.*.harga_tebus'       => 'required|numeric',
            'items.*.jumlah_harga'      => 'required|numeric',
            'items.*.kd_tax'            => 'nullable|string|in:E,EC',
            'items.*.tax_amount'        => 'nullable|numeric',
        ]);

        $vendorPo = DB::transaction(function () use ($validated, $request) {
            $terminal = Terminal::findOrFail($validated['id_terminal']);
            $vendor = Vendor::where('id_vendor', $validated['id_vendor'])
                ->lockForUpdate()
                ->firstOrFail();
            $cabang = Cabang::findOrFail($terminal->id_cabang);

            $nextPoNumber = $vendor->urut_po + 1;
            $bulanRomawi = $this->getBulanRomawi(date('m'));
            $tahun = substr(date('Y'), -2);
            $nomorPo = str_pad($nextPoNumber, 3, '0', STR_PAD_LEFT)
                . '/' . $vendor->inisial
                . '/' . $cabang->inisial_cabang
                . '/' . $bulanRomawi
                . '/' . $tahun;

            // Header PO
            $vendorPo = VendorPo::create([
                'id_vendor'         => $validated['id_vendor'],
                'id_terminal'       => $validated['id_terminal'],
                'nomor_po'          => $nomorPo,
                'kd_tax'            => '-',
                'tanggal_inven'     => $validated['tanggal_inven'],
                'terms'             => $validated['terms'],
                'terms_day'         => $validated['terms_day'],
                'subtotal'          => $validated['subtotal'],
                'ppn11'             => $validated['ppn11'],
                'total_order'       => $validated['total_order'],
                'keterangan'        => $validated['keterangan'],
                'terms_condition'   => $validated['terms_condition'],
                'created_by'        => $request->user()->name,
            ]);
            $vendor->increment('urut_po');

            // Detail PO
            $detailRows = [];
            foreach ($validated['items'] as $item) {
                $detailRows[] = [
                    'id_po'         => $vendorPo->id_po,
                    'id_produk'     => $item['id_produk'],
                    'volume_po'     => $item['volume_po'],
                    'harga_tebus'   => $item['harga_tebus'],
                    'jumlah_harga'  => $item['jumlah_harga'],
                    'kd_tax'        => $item['kd_tax'] ?? null,
                    'tax_amount'    => $item['tax_amount'] ?? 0,
                    'created_time'  => now(),
                ];
            }
            VendorPoProduk::insert($detailRows);

            return $vendorPo;
        });

        return response()->json([
            'message' => 'PO berhasil dibuat',
            'data' => $vendorPo
        ], 201);
    }

    // Fungsi untuk mengubah bulan angka menjadi bulan Romawi
    private function getBulanRomawi($month)
    {
        $months = [
            '01' => 'I',
            '02' => 'II',
            '03' => 'III',
            '04' => 'IV',
            '05' => 'V',
            '06' => 'VI',
            '07' => 'VII',
            '08' => 'VIII',
            '09' => 'IX',
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII'
        ];
        return $months[$month] ?? 'I'; // Default to 'I' if not found
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_vendor'             => 'required|exists:vendors,id_vendor',
            'id_terminal'           => 'required|exists:terminals,id_terminal',
            'nomor_po'              => 'required|string|max:255',
            'tanggal_inven'         => 'required|date',
            'terms'                 => 'nullable|string',
            'terms_day'             => 'nullable|numeric',
            'subtotal'              => 'required|numeric',
            'ppn11'                 => 'required|numeric',
            'total_order'           => 'required|numeric',
            'keterangan'            => 'nullable|string',
            'terms_condition'       => 'nullable|string',

            'items'                 => 'required|array|min:1',
            'items.*.id_produk'     => 'required|exists:produks,id_produk',
            'items.*.volume_po'     => 'required|numeric',
            'items.*.harga_tebus'   => 'required|numeric',
            'items.*.jumlah_harga'  => 'required|numeric',
            'items.*.kd_tax'        => 'nullable|string|in:E,EC',
            'items.*.tax_amount'    => 'nullable|numeric',
        ]);

        $po = VendorPo::findOrFail($id);

        DB::transaction(function () use ($validated, $request, $po) {
            $po->update([
                'id_vendor'         => $validated['id_vendor'],
                'id_terminal'       => $validated['id_terminal'],
                'nomor_po'          => $validated['nomor_po'],
                'kd_tax'            => '-',
                'tanggal_inven'     => $validated['tanggal_inven'],
                'terms'             => $validated['terms'],
                'terms_day'         => $validated['terms_day'],
                'subtotal'          => $validated['subtotal'],
                'ppn11'             => $validated['ppn11'],
                'total_order'       => $validated['total_order'],
                'keterangan'        => $validated['keterangan'],
                'terms_condition'   => $validated['terms_condition'],
                'disposisi_po'      => 0,
                'lastupdate_time'   => now(),
                'lastupdate_by'     => $request->user()->name,
            ]);

            VendorPoProduk::where('id_po', $po->id_po)->delete();

            $detailRows = [];
            foreach ($validated['items'] as $item) {
                $detailRows[] = [
                    'id_po'         => $po->id_po,
                    'id_produk'     => $item['id_produk'],
                    'volume_po'     => $item['volume_po'],
                    'harga_tebus'   => $item['harga_tebus'],
                    'jumlah_harga'  => $item['jumlah_harga'],
                    'kd_tax'        => $item['kd_tax'] ?? null,
                    'tax_amount'    => $item['tax_amount'] ?? 0,
                    'created_time'  => now(),
                ];
            }
            VendorPoProduk::insert($detailRows);
        });

        return response()->json([
            'message' => 'PO berhasil diperbarui',
            'data'    => $po->fresh(),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $po = VendorPo::findOrFail($id);
        $po->disposisi_po      = 2;
        $po->cfo_result        = 1;
        $po->cfo_tgl           = now();
        $po->lastupdate_time   = now();
        $po->lastupdate_by     = $request->user()->name;
        $po->save();

        return response()->json($po);
    }

    public function preview($id)
    {
        $po = VendorPo::with([
            'vendor',
            'terminal',
            'produks.produk',
            'produks.produk.ukuran.satuan',
            'produks.produk.jenis',
        ])->findOrFail($id);

        $leftPath  = public_path('images/logo-new.png');
        $rightPath = public_path('images/logo-crs.png');

        $logoLeft  = file_exists($leftPath)  ? 'data:image/png;base64,' . base64_encode(file_get_contents($leftPath))  : null;
        $logoRight = file_exists($rightPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($rightPath)) : null;

        $qrPayload = (string) $po->nomor_po;

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrPayload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(220)
            ->margin(5)
            ->build();

        $qrBase64 = 'data:image/png;base64,' . base64_encode($result->getString());

        $filename = 'PO-' . str_replace(['/', '\\'], '-', $po->nomor_po) . '.pdf';

        return PDF::loadView('vendorpos.preview', compact('po', 'logoLeft', 'logoRight', 'qrBase64'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont'     => 'DejaVu Sans',
                'tempDir'         => storage_path('app/dompdf-tmp'),
            ])
            ->stream($filename);
    }






    /**
     * Endpoint publik untuk menampilkan data PO (JSON).
     */
    public function publicShow($id)
    {
        $po = VendorPo::with(['vendor', 'terminal', 'produks.produk'])
            ->findOrFail($id);

        return response()->json($po);
    }


    public function destroy($id)
    {
        VendorPo::destroy($id);
        return response()->json(null, 204);
    }
}
