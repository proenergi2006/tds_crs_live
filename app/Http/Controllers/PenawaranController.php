<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\PenawaranOngkos;
use App\Models\Cabang;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\Penawaran\StorePenawaranRequest;
use App\Http\Requests\Penawaran\UpdatePenawaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Mail\PenawaranSubmittedMail;
use App\Mail\PenawaranNeedOmApprovalMail;
use App\Mail\PenawaranRejectedMail;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;




class PenawaranController extends Controller
{
    /** GET /api/penawarans */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('penawaran.viewAny') && $user->cant('penawaran.viewOwn')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->withSum('items as total_volume', 'volume_order');

        if ($user->cant('penawaran.viewAny')) {
            $query->where('user_id', $user->id);
        }

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

    /** GET /api/penawarans/bm — antrian approval BM, lintas-user by design */
    public function indexForBranchManager(Request $request)
    {
        if ($request->user()->cant('penawaran.viewAny')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        // Antrian BM: menunggu BM, menunggu OM (sudah di-approve BM), approved, dan ditolak (BM/OM).
        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [2, 3, 4, 5, 6]);

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

    // Ini bukti pendukung buat Admin Finance, bukan customer_credit_items (itu
    // gak kepakai sama sekali). Sengaja gak difilter status, soalnya Penawaran
    // bisa balik draft diam-diam setelah approved_om (lihat update()) -- kalau
    // di-filter malah bisa nyembunyiin data yang relevan.
    public function lookupForCustomer(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawarans = $customer->penawarans()
            ->withSum('items as total_volume', 'volume_order')
            ->orderByDesc('id_penawaran')
            ->get();

        $data = $penawarans->map(function (Penawaran $penawaran) {
            return [
                'id_penawaran'    => $penawaran->id_penawaran,
                'nomor_penawaran' => $penawaran->nomor_penawaran,
                'masa_berlaku'    => $penawaran->masa_berlaku,
                'sampai_dengan'   => $penawaran->sampai_dengan,
                'harga_dasar'     => $penawaran->harga_dasar,
                'oat'             => $penawaran->oat,
                'total_volume'    => $penawaran->total_volume,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /* Section: QR helpers */

    private function generateNumericCode(int $length = 8): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('',  $length, '9');
        return (string) random_int($min, $max);
    }


    private function buildQrPayload(Penawaran $p): array
    {
        return [
            'id'     => $p->id_penawaran ?? $p->id,
            'nomor'  => (string) $p->nomor_penawaran,
            'cust'   => optional($p->customer)->company_name,
            'valid'  => $p->sampai_dengan,
            'verify' => $this->detailUrl($p->id_penawaran ?? $p->id),
        ];
    }

    private function saveQrPngToStorage(string|array $payload, int $idPenawaran): array
    {
        // pastikan simple-qrcode pakai GD, bukan Imagick
        config(['qrcode.image_backend' => 'gd']);

        $data = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_SLASHES)
            : (string) $payload;

        $pngBinary = QrCode::format('png')
            ->size(512)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($data);

        $dir  = 'qrcodes/' . now()->format('Y/m');
        $safe = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        // pakai timestamp agar unik walau regenerate
        $name = "{$safe}_" . now()->format('YmdHis') . ".png";

        \Storage::disk('public')->put("$dir/$name", $pngBinary);

        $abs = public_path("storage/$dir/$name");
        return [
            'abs_for_pdf' => 'file://' . $abs,
            'url'         => asset("storage/$dir/$name"),
            'rel'         => "$dir/$name",
        ];
    }


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
            'customer',
            'cabang',
            'items.produk.ukuran',
            'user.role'
        ])->findOrFail($id);

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

        $qrPathForPdf = null;   // file://...
        $qrInlineSvg  = null;   // isi svg string (fallback terakhir)

        if (!empty($penawaran->qr_code)) {
            $parsed = parse_url($penawaran->qr_code, PHP_URL_PATH);
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
                $penawaran->forceFill(['qr_code' => $saved['url']])->save();
            } catch (\Throwable $e) {
                report($e);
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

    /** GET /api/penawarans/{id} */
    public function show(Request $request, $id)
    {
        $penawaran = Penawaran::with([
            'customer',
            'cabang',
            'items.produk.jenis',
            'items.produk.ukuran.satuan',
            'produk_harga',
            'ongkos.volume',
            'ongkos.transportir',
            'ongkos.wilayah.provinsi',
            'ongkos.wilayah.kabupaten',
            // Kolom BPS baru, di samping provinsi/kabupaten lama di atas — lama tidak dihapus.
            'ongkos.wilayah.province',
            'ongkos.wilayah.regency',
        ])->findOrFail($id);

        $user = $request->user();
        $allowed = $user->can('penawaran.viewAny')
            || ($user->can('penawaran.viewOwn') && (int) $penawaran->user_id === (int) $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$penawaran->produk_harga && $penawaran->items->isNotEmpty()) {
            $firstProdukId = $penawaran->items->first()->id_produk;
            $harga = \App\Models\ProdukHarga::where('id_produk', $firstProdukId)
                ->orderByDesc('periode_akhir')
                ->first();

            if ($harga) {
                $penawaran->setRelation('produk_harga', $harga);
            }
        }

        // Attach COGS per item untuk weighted-average margin di FE
        foreach ($penawaran->items as $item) {
            $itemHarga = \App\Models\ProdukHarga::where('id_produk', $item->id_produk)
                ->orderByDesc('periode_akhir')
                ->first();
            $item->harga_cogs = $itemHarga->harga_cogs ?? null;
            $item->cogs_basis = $itemHarga->cogs_basis ?? null;
            $item->cogs_basis_label = $itemHarga && $itemHarga->cogs_basis
                ? \App\Enums\ProdukHargaCogsBasis::from($itemHarga->cogs_basis)->label()
                : null;
        }

        return response()->json($penawaran);
    }

    public function store(StorePenawaranRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id ?? ($data['user_id'] ?? null);

        $cabang = Cabang::findOrFail($data['id_cabang']);
        $urut  = (int) $cabang->urut_penawaran + 1;
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
            . '/TDS-PN/' . $cabang->inisial_cabang . '/' . $this->getRomanMonth(date('m')) . '/' . substr(date('Y'), -2);
        $cabang->urut_penawaran = $urut;
        $cabang->save();

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
            'type_pengiriman'            => $data['type_pengiriman'] ?? null,
            'created_at'                 => now(),
            'created_by'                 => optional($request->user())->name,
        ]);



        DB::beginTransaction();
        try {
            $penawaran = Penawaran::create($data);

            if (!empty($data['ongkos'])) {
                foreach ($data['ongkos'] as $o) {
                    $penawaran->ongkos()->create([
                        'penawaran_id'   => $penawaran->id_penawaran,
                        'wilayah_id'     => $o['id_angkut_wilayah'],
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
            $payloadNumber = $this->generateNumericCode(8);

            try {
                $saved = $this->saveQrPngToStorage($payloadNumber, $penawaran->id_penawaran);
            } catch (\Throwable $e) {
                report($e);
                $saved = $this->saveQrSvgToStorage($payloadNumber, $penawaran->id_penawaran);
            }

            $penawaran->forceFill(['qr_code' => $saved['url']])->save();


            DB::table('customers')
                ->where('id_customer', $data['id_customer'])
                ->update([
                    'id_cabang'  => $data['id_cabang'],
                    'updated_at' => now(),
                    'updated_by' => optional($request->user())->name,
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
    public function update(UpdatePenawaranRequest $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);

        $data = $request->validated();

        // NOTE: user_id (ownership) sengaja gak disentuh di sini. Kolom ini cuma
        // diisi sekali waktu store() (create) -- kalau ikut di-overwrite di update(),
        // kepemilikan record bisa diam-diam pindah ke siapapun yang lagi edit,
        // termasuk pemegang penawaran.viewAny yang edit punya user lain.

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

            $penawaran->forceFill([
                'status'             => 'draft',
                'disposisi_penawaran' => '1',     // samakan dengan store() agar Index.vue's getDisposisiLabel tetap match
                'bm_result'          => 0,
                'bm_tanggal'         => now(),
                'catatan_verifikasi' => null,
                'om_result'          => 0,
                'om_tanggal'         => now(),
                'catatan_om'         => null,
            ])->save();

            $penawaran->ongkos()->delete();

            if ($request->has('ongkos')) {
                foreach ($request->ongkos as $o) {
                    $penawaran->ongkos()->create([
                        'penawaran_id'   => $penawaran->id_penawaran,
                        'wilayah_id'     => $o['id_angkut_wilayah'],
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
        if (!empty($qrUrl)) {
            $path = parse_url($qrUrl, PHP_URL_PATH);
            if ($path && str_starts_with($path, '/storage/')) {
                $rel = ltrim(substr($path, strlen('/storage/')), '/');
                \Storage::disk('public')->delete($rel);
            }
        }

        // Sapu juga file lama kalau pernah regenerate -- qr_code cuma nyimpan URL yang aktif sekarang.
        $prefix = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        $all = \Storage::disk('public')->allFiles('qrcodes');
        foreach ($all as $file) {
            $base = basename($file);
            if (str_starts_with($base, $prefix) && (str_ends_with($base, '.png') || str_ends_with($base, '.svg'))) {
                \Storage::disk('public')->delete($file);
            }
        }
    }


    public function destroy(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);

        $user = $request->user();
        $allowed = $user->can('penawaran.manage')
            && ((int) $penawaran->user_id === (int) $user->id || $user->can('penawaran.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
            $penawaran->update([
                'status'              => 'waiting_branch_manager',
                'disposisi_penawaran' => '2',
                'updated_at'          => now(),
            ]);

            // id_role 8 = BM
            $recipients = User::query()
                ->where('id_role', 8)
                ->whereNotNull('email')
                ->pluck('email')
                ->map(fn($e) => trim((string)$e))
                ->filter(fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->all();

            \Log::info('AJUKAN PENAWARAN - BM RECIPIENTS', [
                'id_penawaran' => $penawaran->id_penawaran,
                'nomor'        => $penawaran->nomor_penawaran,
                'recipients'   => $recipients,
            ]);

            if (empty($recipients)) {
                DB::commit();
                return response()->json([
                    'message' => 'Penawaran berhasil diajukan, tetapi email BM tidak ditemukan (id_role=8).',
                ], 200);
            }

            $detailUrl = $this->detailUrl($penawaran->id_penawaran);

            try {
                Mail::to($recipients)->send(new PenawaranSubmittedMail($penawaran, $detailUrl));
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

    public function verifikasi(Request $request, $id)
    {
        if ($request->user()->cant('verification.quotation')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ((int) $request->user()->id_role !== 8) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = Penawaran::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        DB::beginTransaction();
        try {
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

            $creatorEmail = null;

            if (!empty($penawaran->user_id)) {
                $creatorEmail = User::where('id', $penawaran->user_id)
                    ->whereNotNull('email')
                    ->value('email');
            }

            if (!$creatorEmail && !empty($penawaran->created_by)) {
                $creatorEmail = User::where('name', $penawaran->created_by)
                    ->whereNotNull('email')
                    ->value('email');
            }

            if ($creatorEmail) {
                try {
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
        if ($request->user()->cant('verification.quotation')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ((int) $request->user()->id_role !== 8) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = Penawaran::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'rejected_bm',
            'catatan_verifikasi'  => $request->catatan,
            'bm_result'           => '1',
            'bm_tanggal'          => now(),
            'disposisi_penawaran' => 5,
        ]);

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
        if ($request->user()->cant('verification.quotation')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array((int) $request->user()->id_role, [2, 3, 10], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = Penawaran::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'rejected_om',
            'catatan_om'          => $request->catatan,
            'om_result'           => '1',
            'om_tanggal'          => now(),
            'disposisi_penawaran' => 6,
        ]);

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

    /** GET /api/penawarans/om — antrian approval OM, lintas-user by design */
    public function indexForOperationalManager(Request $request)
    {
        if ($request->user()->cant('penawaran.viewAny')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        // Antrian OM: yang nunggu OM (udah di-approve BM), approved, sama yang ditolak
        // OM saja -- ditolak BM gak pernah sampai tahap OM, cuma nongol di menu BM.
        $query = Penawaran::with(['customer', 'cabang', 'items.produk'])
            ->whereIn('disposisi_penawaran', [3, 4, 6]);

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
        if ($request->user()->cant('verification.quotation')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array((int) $request->user()->id_role, [2, 3, 10], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = Penawaran::findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $penawaran->update([
            'status'              => 'approved_om',
            'om_result'           => '1',
            'om_tanggal'          => now(),
            'disposisi_penawaran' => 4,
            'catatan_om'          => $request->catatan,
        ]);

        return response()->json(['message' => 'Status penawaran diperbarui oleh OM']);
    }

    public function getRomanMonth($month)
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
            '12' => 'XII',
        ];
        return $months[$month] ?? '';
    }

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

    /* Section: Email helpers */

    private function detailUrl(int $idPenawaran): string
    {
        return url("/app/penawarans/{$idPenawaran}/detail");
    }

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

    public function previewPdfMultiLang(Request $request, $id)
    {
        $lang = strtolower($request->query('lang', 'id'));
        $priceDetail = $request->query('price_format') === 'detail';

        $penawaran = Penawaran::with(['customer', 'cabang', 'items.produk.ukuran', 'user.role'])
            ->findOrFail($id);

        $u = $penawaran->user;
        if (!$u && !empty($penawaran->created_by)) {
            $u = \App\Models\User::with('role')
                ->where('name', $penawaran->created_by)
                ->first();
        }

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

        $jenis = (int) ($penawaran->jenis_penawaran ?? 1);
        if ($jenis === 2) {
            $view = $lang === 'en' ? 'penawaran.pdf_lub_en' : 'penawaran.pdf_lub_id';
        } else {
            $view = $lang === 'en' ? 'penawaran.pdf_en' : 'penawaran.pdf_id';
        }

        /* QR gaya PO -> base64 PNG. Payload-nya bisa nomor doang atau json --
           biar sama persis kayak PO, dipakai string nomor_penawaran. */
        $qrBase64 = null;

        // string saja -- paling aman untuk discan
        $qrPayload = (string) ($penawaran->nomor_penawaran ?? '');

        if (!empty($qrPayload)) {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrPayload)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(220)
                ->margin(5)
                ->build();

            $qrBase64 = 'data:image/png;base64,' . base64_encode($result->getString());
        }

        $logoLeftPath  = public_path('images/logo-new.png');
        $logoRightPath = public_path('images/logo-crs.png');

        $logoLeft = is_file($logoLeftPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoLeftPath))
            : null;

        $logoRight = is_file($logoRightPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoRightPath))
            : null;

        $pdf = \PDF::loadView($view, compact('penawaran', 'company', 'contact', 'qrBase64', 'logoLeft', 'logoRight', 'priceDetail'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $safeNomor = str_replace(['/', '\\'], '-', $penawaran->nomor_penawaran);
        $suffix = $lang === 'en' ? 'EN' : 'ID';

        return $pdf->stream("Quotation-{$safeNomor}-{$suffix}.pdf");
    }
}
