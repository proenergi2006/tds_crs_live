<?php

namespace App\Http\Controllers;

use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\PenawaranOngkos;
use App\Models\Cabang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Mail\PenawaranSubmittedMail;            // notifikasi ke BM saat diajukan
use App\Mail\PenawaranNeedOmApprovalMail;       // notifikasi ke OM saat approved BM
use App\Mail\PenawaranRejectedMail;             // notifikasi ke pembuat saat ditolak (BM/OM)
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;




class PenawaranController extends Controller
{
    /** GET /api/penawarans */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->withSum('items as total_volume', 'volume_order')
            ->where('user_id', optional($request->user())->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                  ->orWhere('kepada', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

    /** GET /api/penawarans/bm */
    public function indexForBranchManager(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [1, 2, 3, 4, 5, 6]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                  ->orWhere('kepada', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

   /** ============================ QR HELPERS ============================ */
   
   private function generateNumericCode(int $length = 8): string
{
    // contoh length 8 → range 10000000..99999999 (hindari leading zero)
    $min = (int) str_pad('1', $length, '0');
    $max = (int) str_pad('',  $length, '9');
    return (string) random_int($min, $max);
}


   private function buildQrPayload(Penawaran $p): array
   {
       return [
           'id'     => $p->id_penawaran ?? $p->id,
           'nomor'  => (string) $p->nomor_penawaran,
           'cust'   => optional($p->customer)->nama_perusahaan,
           'valid'  => $p->sampai_dengan,
           'verify' => $this->detailUrl($p->id_penawaran ?? $p->id),
       ];
   }

   /** Simpan PNG ke storage/public/qrcodes/YYYY/mm/penawaran-<id>.png */
//    private function saveQrPngToStorage(array $payload, int $idPenawaran): array
//    {
//        // pastikan simple-qrcode pakai GD, bukan Imagick
//        config(['qrcode.image_backend' => 'gd']);

//        $pngBinary = QrCode::format('png')
//            ->size(512)->margin(0)->errorCorrection('M')
//            ->generate(json_encode($payload, JSON_UNESCAPED_SLASHES));

//        $dir = 'qrcodes/' . now()->format('Y/m');
//        $safe = Str::slug("penawaran-{$idPenawaran}");
//        $name = "{$safe}.png";

//        Storage::disk('public')->put("$dir/$name", $pngBinary);

//        $abs = public_path("storage/$dir/$name"); // path lokal absolut
//        return [
//            'abs_for_pdf' => 'file://' . $abs,        // dipakai dompdf
//            'url'         => asset("storage/$dir/$name"), // untuk FE jika perlu
//            'rel'         => "$dir/$name",
//        ];
//    }

/** Simpan PNG ke storage/public/qrcodes/YYYY/mm/penawaran-<id>_<ts>.png */
private function saveQrPngToStorage(string|array $payload, int $idPenawaran): array
{
    // pastikan simple-qrcode pakai GD, bukan Imagick
    config(['qrcode.image_backend' => 'gd']);

    // Jika array → jadikan JSON; jika string → pakai apa adanya
    $data = is_array($payload)
        ? json_encode($payload, JSON_UNESCAPED_SLASHES)
        : (string) $payload;

    $pngBinary = QrCode::format('png')
        ->size(512)
        ->margin(1)                 // beri margin tipis biar mudah dipindai
        ->errorCorrection('M')
        ->generate($data);

    $dir  = 'qrcodes/' . now()->format('Y/m');
    $safe = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
    // pakai timestamp agar unik walau regenerate
    $name = "{$safe}_" . now()->format('YmdHis') . ".png";

    \Storage::disk('public')->put("$dir/$name", $pngBinary);

    $abs = public_path("storage/$dir/$name"); // path lokal absolut
    return [
        'abs_for_pdf' => 'file://' . $abs,            // untuk dompdf/mpdf
        'url'         => asset("storage/$dir/$name"), // untuk FE
        'rel'         => "$dir/$name",
    ];
}


  /** Simpan SVG ke storage (fallback) */
private function saveQrSvgToStorage(string|array $payload, int $idPenawaran): array
{
    $data = is_array($payload)
        ? json_encode($payload, JSON_UNESCAPED_SLASHES)
        : (string) $payload;

    $svg = QrCode::format('svg')
        ->size(512)->margin(1)->errorCorrection('M')
        ->generate($data);

    $dir  = 'qrcodes/' . now()->format('Y/m');
    $safe = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
    $name = "{$safe}_" . now()->format('YmdHis') . ".svg";

    \Storage::disk('public')->put("$dir/$name", $svg);

    $abs = public_path("storage/$dir/$name");
    return [
        'abs_for_pdf' => 'file://' . $abs,
        'url'         => asset("storage/$dir/$name"),
        'rel'         => "$dir/$name",
        'svg'         => $svg,
    ];
}

    
   public function previewPdf($id)
   {
       $penawaran = Penawaran::with([
           'customer', 'cabang', 'items.produk.ukuran', 'user.role'
       ])->findOrFail($id);

       // contact card
       $u = $penawaran->user ?: (
           !empty($penawaran->created_by)
               ? User::with('role')->where('name', $penawaran->created_by)->first()
               : null
       );

       $contact = [
           'name'  => $u?->name ?? ($penawaran->kontak_nama ?? 'Robby Pratama Putra'),
           'role'  => $u?->role?->role_name ?? 'Project Manager',
           'phone' => $u?->telepon ?? $u?->phone ?? $u?->no_hp ?? ($penawaran->kontak_telepon ?? '-'),
           'email' => $u?->email ?? ($penawaran->kontak_email ?? '-'),
       ];

       $company = [
           'nama_perusahaan' => config('app.name'),
           'alamat'          => 'Alamat Perusahaan Anda',
           'telepon'         => '021-xxxxxxx',
           'fax'             => '021-xxxxxxx',
           'logo_path'       => null,
       ];

       /** QR untuk dompdf: pakai file lokal jika ada; regenerate jika perlu */
       $qrPathForPdf = null;   // file://...
       $qrInlineSvg  = null;   // isi svg string (fallback terakhir)

       if (!empty($penawaran->qr_code)) {
           $parsed = parse_url($penawaran->qr_code, PHP_URL_PATH); // /storage/qrcodes/...png
           if ($parsed && Str::startsWith($parsed, '/storage/')) {
               $abs = public_path(ltrim($parsed, '/'));
               if (is_file($abs)) {
                   $qrPathForPdf = 'file://' . $abs;
               }
           }
       }

       if (!$qrPathForPdf) {
           try {
               $saved = $this->saveQrPngToStorage($this->buildQrPayload($penawaran), $penawaran->id_penawaran);
               $qrPathForPdf = $saved['abs_for_pdf'];
               // update kolom agar konsisten
               $penawaran->forceFill(['qr_code' => $saved['url']])->save();
           } catch (\Throwable $e) {
               report($e);
               // fallback SVG inline
               $saved = $this->saveQrSvgToStorage($this->buildQrPayload($penawaran), $penawaran->id_penawaran);
               $svg = preg_replace('/^<\?xml.*?\?>/i', '', $saved['svg']);
               if (!preg_match('/\bwidth=|\bheight=/', $svg)) {
                   $svg = preg_replace('/<svg\b/i', '<svg width="28mm" height="28mm"', $svg, 1);
               }
               $qrInlineSvg = $svg;
               $penawaran->forceFill(['qr_code' => $saved['url']])->save();
           }
       }

       $pdf = \PDF::loadView('penawaran.pdf', compact('penawaran', 'company', 'contact', 'qrPathForPdf', 'qrInlineSvg'))
           ->setPaper('A4', 'portrait');

       $safeNomor = str_replace(['/', '\\'], '-', (string) $penawaran->nomor_penawaran);
       return $pdf->stream("Quotation-{$safeNomor}.pdf");
   }



    


    /** GET /api/penawarans/{id}/preview-lub */
    // public function previewPdflub($id)
    // {
    //     $penawaran = Penawaran::with(['customer', 'cabang', 'items.produk.ukuran'])
    //         ->findOrFail($id);

    //     $company = [
    //         'nama_perusahaan' => config('app.name'),
    //         'alamat'          => 'Alamat Perusahaan Anda',
    //         'telepon'         => '021-xxxxxxx',
    //         'fax'             => '021-xxxxxxx',
    //         'logo_path'       => null,
    //     ];

    //     $pdf = PDF::loadView('penawaran.pdflub', compact('penawaran', 'company'))
    //         ->setPaper('A4', 'portrait');

    //     $safeNomor = str_replace(['/', '\\'], '-', $penawaran->nomor_penawaran);
    //     return $pdf->stream("Quotation-{$safeNomor}.pdf");
    // }

    /** GET /api/penawarans/{id} */
    public function show($id)
    {
        $penawaran = Penawaran::with([
            'customer',
            'cabang',
            'items.produk.jenis',
            'items.produk.ukuran.satuan',
            'produk_harga', // relasi baru
            'ongkos.volume', // ✅
        ])->findOrFail($id);

        if (!$penawaran->produk_harga && $penawaran->items->isNotEmpty()) {
            $firstProdukId = $penawaran->items->first()->id_produk;
            $harga = \App\Models\ProdukHarga::where('id_produk', $firstProdukId)
                ->orderByDesc('periode_akhir')
                ->first();
    
            if ($harga) {
                $penawaran->setRelation('produk_harga', $harga);
            }
        }

        return response()->json($penawaran);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_customer'          => 'required|exists:customers,id_customer',
            'id_cabang'            => 'required|exists:cabangs,id_cabang',
            'masa_berlaku'         => 'required|date',
            'sampai_dengan'        => 'required|date|after_or_equal:masa_berlaku',

              // ===== ONGKOS =====
        'ongkos' => 'nullable|array',
       'ongkos.*.jenis' => 'required|in:KAPAL,TRUCK', // ✅ aktifkan
        'ongkos.*.id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
        'ongkos.*.id_transportir' => 'required|exists:transportirs,id',
        'ongkos.*.id_volume' => 'required|exists:volumes,id_volume', 
        'ongkos.*.ongkos' => 'required|numeric|min:0',


            'items'                => 'required|array|min:1',
            'items.*.id_produk'    => 'required|exists:produks,id_produk',
            'items.*.persen' => 'required|numeric|min:0|max:100',
            'items.*.volume_order' => 'required|numeric|min:0',
            'items.*.harga_tebus'  => 'required|numeric|min:0',
            'type_pengiriman' => 'nullable|in:PROJECT,RETAIL',
            'tipe_pembayaran'      => 'nullable|string|max:100',
            'dp_persen'        => 'nullable|numeric|min:0|max:100',
            'dp_keterangan'    => 'nullable|string|max:100',
            'repayment_persen' => 'nullable|numeric|min:0|max:100',
            'repayment_hari'   => 'nullable|numeric|min:0',


            'order_method'         => 'nullable|string|max:100',
            'toleransi_penyusutan' => 'nullable|numeric|min:0',
            'lokasi_pengiriman'    => 'nullable|string|max:255',
            'metode'               => 'nullable|string|max:100',
            'refund'               => 'nullable|numeric|min:0',
            'other_cost'           => 'nullable|numeric|min:0',
            'perhitungan'          => 'nullable|string',
            'keterangan'           => 'nullable|string',
            'catatan'              => 'nullable|string',
            'syarat_ketentuan'     => 'nullable|string',
            'discount'             => 'nullable|numeric|min:0',
            'oat'                  => 'nullable|numeric|min:0',
            'jenis_penawaran'      => 'nullable|string|max:100',
            'kepada'   => 'nullable|string|max:255',
            'nama'     => 'nullable|string|max:255',
            'jabatan'  => 'nullable|string|max:255',
            'telepon'  => 'nullable|string|max:255',
            'alamat'   => 'nullable|string',
          'abrasi' => 'nullable|string|max:100',

            'user_id'  => 'nullable|exists:users,id',
            'harga_dasar' => 'nullable|numeric|min:0',
            'ppn_harga_dasar' => 'nullable|numeric|min:0',
            'grand_total_harga_dasar' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $data['user_id'] = $request->user()->id ?? ($data['user_id'] ?? null);

        // nomor penawaran per cabang
        $cabang = Cabang::findOrFail($data['id_cabang']);
        $urut  = (int) $cabang->urut_penawaran + 1;
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
               . '/TDS-PN/' . $cabang->inisial_cabang . '/' . $this->getRomanMonth(date('m')) . '/' . substr(date('Y'), -2);
        $cabang->urut_penawaran = $urut; $cabang->save();

        // hitung subtotal/diskon/ppn dll
        $subtotal = 0.0;
        foreach ($data['items'] as $it) {
            $subtotal += ((float)$it['volume_order']) * ((float)$it['harga_tebus']);
        }
        $diskon = $this->toFloat($data['discount'] ?? 0);
        $diskon = max(0.0, min($diskon, $subtotal));
        $setelahDiskon = $subtotal - $diskon;

        $oatPerVol   = $this->toFloat($data['oat'] ?? 0);
        $totalVolume = array_sum(array_column($data['items'], 'volume_order'));
        $totalOat    = $oatPerVol * (float)$totalVolume;

        $ppn11 = round($setelahDiskon * 0.11, 2);
        $total = $setelahDiskon + $ppn11;
        $totalWithOat = $total + $totalOat;

        $data = array_merge($data, [
            'nomor_penawaran'            => $nomor,
            'subtotal'                   => $subtotal,
            'harga_tebus_setelah_diskon' => $setelahDiskon,
            'ppn11'                      => $ppn11,
            'total'                      => $total,
            'total_with_oat'             => $totalWithOat,
            'discount'                   => $diskon,
            'status'                     => 'draft',
            'disposisi_penawaran'        => '1',
            'type_pengiriman'            => $data['type_pengiriman'] ?? null, // ✅ field baru
            'created_at'                 => now(),
            'created_by'                 => optional($request->user())->name,
        ]);

       

        DB::beginTransaction();
        try {
            $penawaran = Penawaran::create($data);

            if (!empty($data['ongkos'])) {
                foreach ($data['ongkos'] as $o) {
                    $penawaran->ongkos()->create([
                         'penawaran_id'   => $penawaran->id_penawaran, // 🔥 WAJIB
                       'wilayah_id'     => $o['id_angkut_wilayah'], // ✅ mapping
                        'transportir_id' => $o['id_transportir'],
                        'jenis'          => $o['jenis'], 
                     'volume_id' => $o['id_volume'], 
                        'ongkos'         => $o['ongkos'],
                      
                    ]);
                }
            }
        

            foreach ($data['items'] as $it) {
                PenawaranItem::create([
                    'id_penawaran' => $penawaran->id_penawaran,
                    'id_produk'    => $it['id_produk'],
                    'volume_order' => $it['volume_order'],
                    'persen'       => $it['persen'],
                    'harga_tebus'  => $it['harga_tebus'],
                    'jumlah_harga' => $it['volume_order'] * $it['harga_tebus'],
                ]);
            }

            // generate & simpan QR angka random (TANPA simpan token ke DB)
$penawaran->refresh();
$payloadNumber = $this->generateNumericCode(8); // ubah length kalau perlu

try {
    $saved = $this->saveQrPngToStorage($payloadNumber, $penawaran->id_penawaran);
} catch (\Throwable $e) {
    report($e);
    $saved = $this->saveQrSvgToStorage($payloadNumber, $penawaran->id_penawaran);
}

$penawaran->forceFill(['qr_code' => $saved['url']])->save();


            // update customer metadata (tidak mengganggu utama)
            DB::table('customers')
                ->where('id_customer', $data['id_customer'])
                ->update([
                    'need_update'     => 1,
                    'id_cabang'       => $data['id_cabang'],
                    'lastupdate_time' => now(),
                    'lastupdate_by'   => optional($request->user())->name,
                ]);

            DB::commit();

            $penawaran->load(['customer', 'cabang', 'items.produk']);
            return response()->json($penawaran, 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Gagal menyimpan penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /** PUT /api/penawarans/{id} — DISKON = nominal rupiah */
    public function update(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id_customer'          => 'required|exists:customers,id_customer',
            'id_cabang'            => 'required|exists:cabangs,id_cabang',
            'masa_berlaku'         => 'required|date',
            'sampai_dengan'        => 'required|date|after_or_equal:masa_berlaku',

          'ongkos' => 'nullable|array',
          'ongkos.*.jenis' => 'required|in:KAPAL,TRUCK', // ✅ aktifkan
          'ongkos.*.id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
          'ongkos.*.id_transportir' => 'required|exists:transportirs,id',
       'ongkos.*.id_volume' => 'required|exists:volumes,id_volume',  // ✅
            'ongkos.*.ongkos' => 'required|numeric|min:0',


            'items'                => 'required|array|min:1',
            'items.*.id_produk'    => 'required|exists:produks,id_produk',
            'items.*.persen' => 'required|numeric|min:0|max:100',
            'items.*.volume_order' => 'required|numeric|min:0',
            'items.*.harga_tebus'  => 'required|numeric|min:0',
            'tipe_pembayaran'      => 'nullable|string|max:100',
            'dp_persen'        => 'nullable|numeric|min:0|max:100',
            'dp_keterangan'    => 'nullable|string|max:100',
            'repayment_persen' => 'nullable|numeric|min:0|max:100',
            'repayment_hari'   => 'nullable|numeric|min:0',

            'order_method'         => 'nullable|string|max:100',
            'toleransi_penyusutan' => 'nullable|numeric|min:0',
            'lokasi_pengiriman'    => 'nullable|string|max:255',
            'type_pengiriman' => 'nullable|in:PROJECT,RETAIL',
            'metode'               => 'nullable|string|max:100',
            'refund'               => 'nullable|numeric|min:0',
            'other_cost'           => 'nullable|numeric|min:0',
            'perhitungan'          => 'nullable|string',
            'keterangan'           => 'nullable|string',
            'catatan'              => 'nullable|string',
            'syarat_ketentuan'     => 'nullable|string',
            'discount'             => 'nullable|numeric|min:0',
            'oat'                  => 'nullable|numeric|min:0',
            'kepada'   => 'nullable|string|max:255',
            'nama'     => 'nullable|string|max:255',
            'jabatan'  => 'nullable|string|max:255',
            'telepon'  => 'nullable|string|max:255',
            'alamat'   => 'nullable|string',
           'abrasi' => 'nullable|string|max:100',

            'user_id' => 'nullable|exists:users,id',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        $data['user_id'] = $request->user()->id ?? ($data['user_id'] ?? null);


        $subtotal = 0.0;
        foreach ($data['items'] as $it) {
            $subtotal += ((float)$it['volume_order']) * ((float)$it['harga_tebus']);
        }

        $diskon = $this->toFloat($data['discount'] ?? 0);
        if ($diskon < 0) $diskon = 0.0;
        if ($diskon > $subtotal) $diskon = $subtotal;

        $setelahDiskon = $subtotal - $diskon;

        $oatPerVolume = $this->toFloat($data['oat'] ?? 0);
        $totalVolume  = array_sum(array_column($data['items'], 'volume_order'));
        $totalOat     = $oatPerVolume * (float)$totalVolume;

        $ppn11 = round($setelahDiskon * 0.11, 2);
        $total = $setelahDiskon + $ppn11;
        $totalWithOat = $total + $totalOat;

        $data['subtotal']                   = $subtotal;
        $data['harga_tebus_setelah_diskon'] = $setelahDiskon;
        $data['ppn11']                      = $ppn11;
        $data['total']                      = $total;
        $data['total_with_oat']             = $totalWithOat;
        $data['type_pengiriman'] = $data['type_pengiriman'] ?? $penawaran->type_pengiriman;
        $data['discount']                   = $diskon;
        $data['updated_at']                 = now();
        $data['updated_by']                 = $request->user()->name ?? null;


        

        DB::beginTransaction();
        try {
            $penawaran->update($data);

            // ✅ RESET DISPOSISI & APPROVAL karena penawaran diubah
                $penawaran->forceFill([
                    'status'             => 'draft', // opsional kalau mau status juga balik draft
                    'disposisi_penawaran'=> 0,       // sesuai permintaan kamu (draft)

                    // BM reset
                    'bm_result'          => 0,
                    'bm_tanggal'         => now(),
                    'catatan_verifikasi' => null,

                    // OM reset (pastikan kolomnya ada di tabel & model)
                    'om_result'          => 0,
                    'om_tanggal'         => now(),
                    'catatan_om'         => null,
                ])->save();

            $penawaran->ongkos()->delete();

        if ($request->has('ongkos')) {
            foreach ($request->ongkos as $o) {
                $penawaran->ongkos()->create([
                    'penawaran_id'   => $penawaran->id_penawaran, // 🔥 WAJIB
                    'wilayah_id'     => $o['id_angkut_wilayah'], // ✅ mapping
                        'transportir_id' => $o['id_transportir'],
                        'jenis'          => $o['jenis'],    
                      'volume_id' => $o['id_volume'],

                        'ongkos'         => $o['ongkos'],
                ]);
            }
        }

            PenawaranItem::where('id_penawaran', $penawaran->id_penawaran)->delete();
            foreach ($data['items'] as $it) {
                PenawaranItem::create([
                    'id_penawaran' => $penawaran->id_penawaran,
                    'id_produk'    => $it['id_produk'],
                    'volume_order' => $it['volume_order'],
                    'persen' => $it['persen'],
                    'harga_tebus'  => $it['harga_tebus'],
                    'jumlah_harga' => $it['volume_order'] * $it['harga_tebus'],
                ]);
            }

            DB::commit();
            $penawaran->load(['customer', 'cabang', 'items.produk']);
            return response()->json($penawaran);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal update penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function deleteQrFiles(int $idPenawaran, ?string $qrUrl = null): void
{
    // 1) Hapus file yang sekarang dipakai (dari kolom qr_code → URL /storage/…)
    if (!empty($qrUrl)) {
        $path = parse_url($qrUrl, PHP_URL_PATH); // contoh: /storage/qrcodes/2025/10/penawaran-123_20251006.png
        if ($path && str_starts_with($path, '/storage/')) {
            $rel = ltrim(substr($path, strlen('/storage/')), '/'); // qrcodes/2025/10/penawaran-123_....
            \Storage::disk('public')->delete($rel);
        }
    }

    // 2) Sapu bersih file-file lama (jika pernah regenerate): penawaran-<id>_*.png/svg
    $prefix = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}"); // sama seperti saat simpan
    $all = \Storage::disk('public')->allFiles('qrcodes'); // jalan di subfolder Tahunan/Bulanan
    foreach ($all as $file) {
        $base = basename($file);
        if (str_starts_with($base, $prefix) && (str_ends_with($base, '.png') || str_ends_with($base, '.svg'))) {
            \Storage::disk('public')->delete($file);
        }
    }
}


public function destroy($id)
{
    $penawaran = Penawaran::findOrFail($id);

    // simpan data yang dibutuhkan sebelum record dihapus
    $idPenawaran = (int)($penawaran->id_penawaran ?? $penawaran->id);
    $qrUrl = $penawaran->qr_code;

    try {
        $this->deleteQrFiles($idPenawaran, $qrUrl);
    } catch (\Throwable $e) {
        report($e); // kalau gagal hapus file, tetap lanjut hapus data
    }

    $penawaran->delete();

    return response()->json(null, 204);
}


    /** DELETE /api/penawarans/{id} */
    // public function destroy($id)
    // {
    //     $penawaran = Penawaran::findOrFail($id);
    //     $penawaran->delete();
    //     return response()->json(null, 204);
    // }

    /** PATCH /api/penawarans/{id}/ajukan — kirim email ke BM */
    // public function ajukan($id)
    // {
    //     $penawaran = Penawaran::with(['customer','cabang'])->findOrFail($id);

    //     if ($penawaran->status !== 'draft') {
    //         return response()->json(['message' => 'Penawaran sudah diajukan sebelumnya'], 400);
    //     }

    //     $penawaran->update([
    //         'status'              => 'waiting_branch_manager',
    //         'disposisi_penawaran' => '2',
    //         'updated_at'          => now(),
    //     ]);

    //     // Ambil email Branch Manager (id_role = 8)
    //     $recipients = \App\Models\User::where('id_role', 8)
    //     ->whereNotNull('email')
    //     ->pluck('email')
    //     ->toArray();


    //    if (!empty($recipients)) {
    //     try {
    //         $detailUrl = $this->detailUrl($penawaran->id_penawaran);

    //         Mail::to($recipients)->send(
    //             new PenawaranSubmittedMail($penawaran, $detailUrl)
    //         );
    //     } catch (\Throwable $e) {
    //         report($e); // jangan gagalkan proses utama
    //     }
    // }

    //     return response()->json(['message' => 'Penawaran berhasil diajukan']);
    // }

    /** PATCH /api/penawarans/{id}/ajukan — kirim email ke BM */
public function ajukan($id)
{
    $penawaran = Penawaran::with(['customer', 'cabang'])->findOrFail($id);

    if ($penawaran->status !== 'draft') {
        return response()->json([
            'message' => 'Penawaran sudah diajukan sebelumnya'
        ], 400);
    }

    DB::beginTransaction();
    try {
        // 1) update status penawaran
        $penawaran->update([
            'status'              => 'waiting_branch_manager',
            'disposisi_penawaran' => '2',
            'updated_at'          => now(),
        ]);

        // 2) ambil email BM (id_role = 8)
        $recipients = User::query()
            ->where('id_role', 8)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn($e) => trim((string)$e))
            ->filter(fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        // 3) log untuk debug
        \Log::info('AJUKAN PENAWARAN - BM RECIPIENTS', [
            'id_penawaran' => $penawaran->id_penawaran,
            'nomor'        => $penawaran->nomor_penawaran,
            'recipients'   => $recipients,
        ]);

        // 4) kalau BM kosong, jangan dianggap sukses kirim email
        if (empty($recipients)) {
            DB::commit();
            return response()->json([
                'message' => 'Penawaran berhasil diajukan, tetapi email BM tidak ditemukan (id_role=8).',
            ], 200);
        }

        // 5) kirim email
        $detailUrl = $this->detailUrl($penawaran->id_penawaran);

        try {
            // opsi 1: pakai Mailable kamu
            Mail::to($recipients)->send(new PenawaranSubmittedMail($penawaran, $detailUrl));

            // opsi 2 (debug cepat): kalau mau pastikan SMTP jalan, pakai raw:
            // Mail::raw("Penawaran {$penawaran->nomor_penawaran} diajukan. Link: {$detailUrl}", function ($m) use ($recipients) {
            //     $m->to($recipients)->subject('Penawaran Diajukan');
            // });

        } catch (\Throwable $mailErr) {
            // rollback status biar konsisten kalau email wajib sukses
            DB::rollBack();

            \Log::error('AJUKAN PENAWARAN - GAGAL KIRIM EMAIL BM', [
                'id_penawaran' => $penawaran->id_penawaran,
                'error'        => $mailErr->getMessage(),
                'trace'        => $mailErr->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Penawaran gagal diajukan karena email BM gagal dikirim.',
                'error'   => $mailErr->getMessage(),
            ], 500);
        }

        DB::commit();

        return response()->json([
            'message' => 'Penawaran berhasil diajukan dan email berhasil dikirim ke Branch Manager.',
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        \Log::error('AJUKAN PENAWARAN - ERROR', [
            'id'    => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'Gagal mengajukan penawaran',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


   
    // public function verifikasi(Request $request, $id)
    // {
    //     $penawaran = Penawaran::with(['customer','cabang'])->findOrFail($id);
    //     $request->validate(['catatan' => 'nullable|string']);

    //     $penawaran->update([
    //         'status'              => 'approved_bm',
    //         'catatan_verifikasi'  => $request->catatan,
    //         'bm_result'           => '1',
    //         'bm_tanggal'          => now(),
    //         'approved_at'         => now(),
    //         'approved_by'         => $request->user()->name ?? 'BM',
    //         'disposisi_penawaran' => 3, // next: OM
    //     ]);

    //     // >>> Kirim email ke semua OM untuk verifikasi <<<
    //     $omRecipients = $this->getRoleEmails(['CEO', 'CEO', 'CEO']);
    //     if (!empty($omRecipients)) {
    //         try {
    //             $detailUrl = $this->detailUrl($penawaran->id_penawaran);
    //             Mail::to($omRecipients)->send(new PenawaranNeedOmApprovalMail($penawaran, $detailUrl));
    //         } catch (\Throwable $e) {
    //             report($e);
    //         }
    //     }

    //     return response()->json(['message' => 'Status penawaran diperbarui']);
    // }

    public function verifikasi(Request $request, $id)
{
    $penawaran = Penawaran::with(['customer','cabang'])->findOrFail($id);
    $request->validate(['catatan' => 'nullable|string']);

    DB::beginTransaction();
    try {
        // 1) Update status penawaran
        $penawaran->update([
            'status'              => 'approved_bm',
            'catatan_verifikasi'  => $request->catatan,
            'bm_result'           => '1',
            'bm_tanggal'          => now(),
            'approved_at'         => now(),
            'approved_by'         => $request->user()->name ?? 'BM',
            'disposisi_penawaran' => 3, // next: OM/CEO
        ]);

        $detailUrl = $this->detailUrl($penawaran->id_penawaran);

        /**
         * 2) EMAIL KE USER PEMBUAT (penawaran.user_id)
         */
        $creatorEmail = null;

        if (!empty($penawaran->user_id)) {
            $creatorEmail = User::where('id', $penawaran->user_id)
                ->whereNotNull('email')
                ->value('email');
        }

        // fallback kalau user_id kosong: pakai created_by
        if (!$creatorEmail && !empty($penawaran->created_by)) {
            $creatorEmail = User::where('name', $penawaran->created_by)
                ->whereNotNull('email')
                ->value('email');
        }

        if ($creatorEmail) {
            try {
                // Pakai mailable khusus update status (ideal). Kalau belum ada, sementara bisa pakai raw.
                Mail::raw(
                    "Penawaran {$penawaran->nomor_penawaran} sudah disetujui Branch Manager.\nMenunggu approval OM/CEO.\n\nLink: {$detailUrl}",
                    function ($m) use ($creatorEmail) {
                        $m->to($creatorEmail)->subject('Update Penawaran: Disetujui BM');
                    }
                );
            } catch (\Throwable $e) {
                report($e);
                \Log::error('EMAIL CREATOR UPDATE FAILED', [
                    'id_penawaran' => $penawaran->id_penawaran,
                    'email' => $creatorEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            \Log::warning('CREATOR EMAIL NOT FOUND', [
                'id_penawaran' => $penawaran->id_penawaran,
                'user_id' => $penawaran->user_id,
                'created_by' => $penawaran->created_by,
            ]);
        }

        /**
         * 3) EMAIL KE OM (id_role = 10) dan CEO (id_role = 2)
         */
        $approverRecipients = User::query()
            ->whereIn('id_role', [10, 2])          // OM=10, CEO=2
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn($e) => trim((string)$e))
            ->filter(fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        \Log::info('APPROVER RECIPIENTS (OM+CEO)', [
            'id_penawaran' => $penawaran->id_penawaran,
            'recipients' => $approverRecipients,
        ]);

        if (!empty($approverRecipients)) {
            try {
                // Kamu bisa pakai mailable existing untuk approval OM:
                Mail::to($approverRecipients)->send(
                    new PenawaranNeedOmApprovalMail($penawaran, $detailUrl)
                );
            } catch (\Throwable $e) {
                report($e);
                \Log::error('EMAIL OM/CEO APPROVAL FAILED', [
                    'id_penawaran' => $penawaran->id_penawaran,
                    'recipients' => $approverRecipients,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        DB::commit();

        return response()->json(['message' => 'Penawaran disetujui BM & notifikasi terkirim']);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Gagal verifikasi penawaran',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    /** POST /api/penawarans/{id}/tolak-bm */
    public function tolakbm(Request $request, $id)
    {
        $penawaran = Penawaran::with(['customer','cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'rejected_bm',
            'catatan_verifikasi'  => $request->catatan,
            'bm_result'           => '1',
            'bm_tanggal'          => now(),
            'disposisi_penawaran' => 5,
        ]);

        // >>> Kirim email ke pembuat penawaran <<<
        $creatorEmail = $this->getCreatorEmail($penawaran->created_by);
        if ($creatorEmail) {
            try {
                $detailUrl = $this->detailUrl($penawaran->id_penawaran);
                Mail::to($creatorEmail)->send(
                    new PenawaranRejectedMail($penawaran, $request->catatan, 'Ditolak oleh Branch Manager', $detailUrl)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => 'Status penawaran Ditolak']);
    }

    /** POST /api/penawarans/{id}/tolak-om */
    public function tolakom(Request $request, $id)
    {
        $penawaran = Penawaran::with(['customer','cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'rejected_om',
            'catatan_om'          => $request->catatan,
            'om_result'           => '1',
            'om_tanggal'          => now(),
            'disposisi_penawaran' => 6,
        ]);

        // >>> Kirim email ke pembuat penawaran <<<
        $creatorEmail = $this->getCreatorEmail($penawaran->created_by);
        if ($creatorEmail) {
            try {
                $detailUrl = $this->detailUrl($penawaran->id_penawaran);
                Mail::to($creatorEmail)->send(
                    new PenawaranRejectedMail($penawaran, $request->catatan, 'Ditolak oleh Operational Manager', $detailUrl)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => 'Status penawaran Ditolak']);
    }

    /** GET /api/penawarans/om */
    public function indexForOperationalManager(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [1, 2, 3, 4, 5, 6]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                  ->orWhere('kepada', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

    /** POST /api/penawarans/{id}/verifikasi-om */
    public function verifikasiOm(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'approved_om',
            'om_result'           => '1',
            'om_tanggal'          => now(),
            'disposisi_penawaran' => 4,
            'catatan_om'          => $request->catatan,
        ]);

        // (opsional) Bisa juga kirim email ke creator bahwa penawaran sudah disetujui OM
        return response()->json(['message' => 'Status penawaran diperbarui oleh OM']);
    }

    /** Helper: bulan Romawi */
    public function getRomanMonth($month)
    {
        $months = [
            '01' => 'I', '02' => 'II', '03' => 'III', '04' => 'IV',
            '05' => 'V', '06' => 'VI', '07' => 'VII','08' => 'VIII',
            '09' => 'IX','10' => 'X',  '11' => 'XI','12' => 'XII',
        ];
        return $months[$month] ?? '';
    }

    /** Helper: normalisasi angka lokal -> float */
    private function toFloat($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v;

        $s = trim((string)$v);
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s); // hapus pemisah ribuan
            $s = str_replace(',', '.', $s); // desimal ke titik
            return (float)$s;
        }
        if (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }
        return (float)$s;
    }

    /** ====== EMAIL HELPERS ====== */

    private function detailUrl(int $idPenawaran): string
    {
        // sesuaikan dengan route FE kamu
        return url("/app/penawarans/{$idPenawaran}/detail");
    }

    /**
     * Ambil email semua user dengan role_name di daftar $roleNames.
     * Menghormati kolom is_active jika ada.
     */
    private function getRoleEmails(array $roleNames): array
    {
        $roleIds = Role::whereIn('role_name', $roleNames)->pluck('id_role');

        $query = User::whereIn('id_role', $roleIds)
            ->whereNotNull('email');

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->pluck('email')->unique()->values()->all();
    }

    /**
     * Ambil email pembuat penawaran berdasarkan kolom created_by (nama user).
     * Return null jika tidak ditemukan.
     */
    private function getCreatorEmail(?string $creatorName): ?string
    {
        if (!$creatorName) return null;

        $query = User::where('name', $creatorName)->whereNotNull('email');

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        $user = $query->first();
        return $user?->email;
    }

    // PenawaranController.php (tambahkan method baru ini)
public function previewPdfMultiLang(Request $request, $id)
{
    $lang = strtolower($request->query('lang', 'id')); // default: Indonesia
    $penawaran = Penawaran::with(['customer', 'cabang', 'items.produk.ukuran', 'user.role'])->findOrFail($id);

    $u = $penawaran->user;
    if (!$u && !empty($penawaran->created_by)) {
        $u = \App\Models\User::with('role')
            ->where('name', $penawaran->created_by)
            ->first();
    }

       // Susun contact dengan null-safe
       $contact = [
        'name'  => $u?->name ?? ($penawaran->kontak_nama ?? 'Robby Pratama Putra'),
        'role'  => $u?->role?->role_name ?? 'Project Manager',
        // sesuaikan nama kolom telepon di users kamu (telepon/phone/no_hp)
        'phone' => $u?->telepon ?? $u?->phone ?? $u?->no_hp ?? ($penawaran->kontak_telepon ?? '-'),
        'email' => $u?->email ?? ($penawaran->kontak_email ?? '-'),
    ];

    $company = [
        'nama_perusahaan' => config('app.name'),
        'alamat'          => 'Alamat Perusahaan Anda',
        'telepon'         => '021-xxxxxxx',
        'fax'             => '021-xxxxxxx',
        'logo_path'       => null,
    ];

    // Pilih view berdasarkan bahasa + jenis_penawaran
    // Misal:
    // - Indonesia:  resources/views/penawaran/pdf_id.blade.php
    // - English:    resources/views/penawaran/pdf_en.blade.php
    // - Untuk jenis lubricant, pakai pdf_lub_id / pdf_lub_en
    $jenis = (int) ($penawaran->jenis_penawaran ?? 1);

    if ($jenis === 2) {
        $view = $lang === 'en' ? 'penawaran.pdf_lub_en' : 'penawaran.pdf_lub_id';
    } else {
        $view = $lang === 'en' ? 'penawaran.pdf_en' : 'penawaran.pdf_id';
    }


     /** QR untuk dompdf: pakai file lokal jika ada; regenerate jika perlu */
     $qrPathForPdf = null;   // file://...
     $qrInlineSvg  = null;   // isi svg string (fallback terakhir)

     if (!empty($penawaran->qr_code)) {
         $parsed = parse_url($penawaran->qr_code, PHP_URL_PATH); // /storage/qrcodes/...png
         if ($parsed && Str::startsWith($parsed, '/storage/')) {
             $abs = public_path(ltrim($parsed, '/'));
             if (is_file($abs)) {
                 $qrPathForPdf = 'file://' . $abs;
             }
         }
     }

     if (!$qrPathForPdf) {
         try {
             $saved = $this->saveQrPngToStorage($this->buildQrPayload($penawaran), $penawaran->id_penawaran);
             $qrPathForPdf = $saved['abs_for_pdf'];
             // update kolom agar konsisten
             $penawaran->forceFill(['qr_code' => $saved['url']])->save();
         } catch (\Throwable $e) {
             report($e);
             // fallback SVG inline
             $saved = $this->saveQrSvgToStorage($this->buildQrPayload($penawaran), $penawaran->id_penawaran);
             $svg = preg_replace('/^<\?xml.*?\?>/i', '', $saved['svg']);
             if (!preg_match('/\bwidth=|\bheight=/', $svg)) {
                 $svg = preg_replace('/<svg\b/i', '<svg width="28mm" height="28mm"', $svg, 1);
             }
             $qrInlineSvg = $svg;
             $penawaran->forceFill(['qr_code' => $saved['url']])->save();
         }
     }



    $pdf = \PDF::loadView($view, compact('penawaran', 'company','contact'))->setPaper('A4', 'portrait');

    $safeNomor = str_replace(['/', '\\'], '-', $penawaran->nomor_penawaran);
    $suffix = $lang === 'en' ? 'EN' : 'ID';
    return $pdf->stream("Quotation-{$safeNomor}-{$suffix}.pdf");
}




}
