<?php

namespace App\Http\Controllers;

use App\Models\PenawaranProenergi;
use App\Models\PenawaranItemProenergi;
use App\Models\Cabang;
use Illuminate\Http\Request;
use App\Http\Requests\Penawaran\StorePenawaranProenergiRequest;
use App\Http\Requests\Penawaran\UpdatePenawaranProenergiRequest;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Actions\Penawaran\SubmitPenawaranAction;
use App\Actions\Penawaran\ApprovePenawaranBmAction;
use App\Actions\Penawaran\RejectPenawaranBmAction;
use App\Actions\Penawaran\ApprovePenawaranOmAction;
use App\Actions\Penawaran\RejectPenawaranOmAction;
use App\Actions\Penawaran\ResolvePenawaranBmQueueAction;
use App\Actions\Penawaran\ResolvePenawaranOmQueueAction;
use App\Actions\Penawaran\GeneratePenawaranPdfAction;
use App\Enums\ProdukHargaCogsBasis;
use App\Models\ProdukHarga;
use App\Support\Approval\PenawaranApprovalStepsBuilder;

class PenawaranProenergiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('penawaran.proenergi.viewAny') && $user->cant('penawaran.proenergi.viewOwn')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = PenawaranProenergi::with(['customer', 'cabang', 'items.produk.jenis', 'items.produk.ukuran.satuan'])
            ->withSum('items as total_volume', 'volume_order');

        if ($user->cant('penawaran.proenergi.viewAny')) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            // Kontak tujuan bukan kolom sendiri lagi; search menjangkau nama perusahaan customer + nama kontaknya.
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('customerContact', fn ($cq) => $cq->where('full_name', 'like', "%{$search}%"));
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

    public function store(StorePenawaranProenergiRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id ?? ($data['user_id'] ?? null);

        $cabang = Cabang::findOrFail($data['id_cabang']);
        $urut  = (int) $cabang->urut_penawaran + 1;
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
            . '/PE-PN/' . $cabang->inisial_cabang . '/' . $this->getRomanMonth(date('m')) . '/' . substr(date('Y'), -2);
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
            $penawaran = PenawaranProenergi::create($data);

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
                PenawaranItemProenergi::create([
                    'id_penawaran' => $penawaran->id_penawaran,
                    'id_produk'    => $it['id_produk'],
                    'volume_order' => $it['volume_order'],
                    'persen'       => $it['persen'],
                    'harga_tebus'  => $it['harga_tebus'],
                    'jumlah_harga' => $it['volume_order'] * $it['harga_tebus'],
                ]);
            }

            // token QR sengaja gak disimpan ke DB
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

    public function show(Request $request, $id)
    {
        $penawaran = PenawaranProenergi::with([
            'customer',
            'customer.headOfficeAddress',
            'customerContact',
            'cabang',
            'items.produk.jenis',
            'items.produk.ukuran.satuan',
            'ongkos.volume',
            'ongkos.transportir',
            'ongkos.wilayah.provinsi',
            'ongkos.wilayah.kabupaten',
            'ongkos.wilayah.province',
            'ongkos.wilayah.regency',
            'documentApprovals.steps.templateStep',
            'documentApprovals.steps.actor',
        ])->findOrFail($id);

        $user = $request->user();
        $allowed = $user->can('penawaran.proenergi.viewAny')
            || ($user->can('penawaran.proenergi.viewOwn') && (int) $penawaran->user_id === (int) $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($penawaran->items->isNotEmpty()) {
            $firstProdukId = $penawaran->items->first()->id_produk;

            $harga = ProdukHarga::query()
                ->where('id_produk', $firstProdukId)
                ->where('id_cabang', $penawaran->id_cabang)
                ->whereDate('periode_awal', '<=', $penawaran->masa_berlaku)
                ->whereDate('periode_akhir', '>=', $penawaran->masa_berlaku)
                ->orderByDesc('periode_akhir')
                ->first();

            if ($harga) {
                $penawaran->setRelation('produk_harga', $harga);
            } else {
                $penawaran->setRelation('produk_harga', null);
            }
        } else {
            $penawaran->setRelation('produk_harga', null);
        }

        // COGS per item ini buat weighted-average margin di FE
        foreach ($penawaran->items as $item) {
            $itemHarga = ProdukHarga::query()
                ->where('id_produk', $item->id_produk)
                ->where('id_cabang', $penawaran->id_cabang)
                ->whereDate('periode_awal', '<=', $penawaran->masa_berlaku)
                ->whereDate('periode_akhir', '>=', $penawaran->masa_berlaku)
                ->orderByDesc('periode_akhir')
                ->first();
            $item->harga_cogs = $itemHarga->harga_cogs ?? null;
            $item->cogs_basis = $itemHarga->cogs_basis ?? null;
            $item->cogs_basis_label = $itemHarga && $itemHarga->cogs_basis
                ? ProdukHargaCogsBasis::from($itemHarga->cogs_basis)->label()
                : null;
        }

        $payload = $penawaran->makeHidden('documentApprovals')->toArray();
        $payload['approval_attempts'] = app(PenawaranApprovalStepsBuilder::class)->buildAttempts($penawaran);

        return response()->json($payload);
    }

    public function update(UpdatePenawaranProenergiRequest $request, $id)
    {
        $penawaran = PenawaranProenergi::findOrFail($id);

        $data = $request->validated();

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

            if ($penawaran->status !== 'draft') {
                (new \App\Services\Approval\DocumentApprovalService())->cancelActiveCycle($penawaran);
            }

            $penawaran->forceFill([
                'status'             => 'draft',
                'disposisi_penawaran' => '1',
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

            PenawaranItemProenergi::where('id_penawaran', $penawaran->id_penawaran)->delete();
            foreach ($data['items'] as $it) {
                PenawaranItemProenergi::create([
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

    public function destroy(Request $request, $id)
    {
        $penawaran = PenawaranProenergi::findOrFail($id);

        $user = $request->user();
        $allowed = $user->can('penawaran.proenergi.manage')
            && ((int) $penawaran->user_id === (int) $user->id || $user->can('penawaran.proenergi.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $idPenawaran = (int)($penawaran->id_penawaran ?? $penawaran->id);
        $qrUrl = $penawaran->qr_code;

        try {
            $this->deleteQrFiles($idPenawaran, $qrUrl);
        } catch (\Throwable $e) {
            report($e);
        }

        $penawaran->delete();

        return response()->json(null, 204);
    }

    public function ajukan($id)
    {
        $penawaran = PenawaranProenergi::with(['customer', 'cabang'])->findOrFail($id);

        $result = (new SubmitPenawaranAction())->execute(
            $penawaran,
            $this->bmVerificationUrl($penawaran->id_penawaran)
        );

        $response = ['message' => $result['message']];
        if (array_key_exists('error', $result)) {
            $response['error'] = $result['error'];
        }

        return response()->json($response, $result['status'] ?? 200);
    }

    public function indexForBranchManager(Request $request)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-bm')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = (new ResolvePenawaranBmQueueAction())->execute(
            PenawaranProenergi::class,
            $request->query('search'),
            (int) $request->query('per_page', 10)
        );

        return response()->json($data);
    }

    public function verifikasi(Request $request, $id)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-bm')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = PenawaranProenergi::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $result = (new ApprovePenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $request->user()->name ?? 'BM',
            $this->detailUrl($penawaran->id_penawaran),
            $this->omVerificationUrl($penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']], $result['status'] ?? 200);
    }

    public function tolakbm(Request $request, $id)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-bm')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = PenawaranProenergi::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'required|string']);

        $result = (new RejectPenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']]);
    }

    public function indexForOperationalManager(Request $request)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-om')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = (new ResolvePenawaranOmQueueAction())->execute(
            PenawaranProenergi::class,
            $request->query('search'),
            (int) $request->query('per_page', 10)
        );

        return response()->json($data);
    }

    public function verifikasiOm(Request $request, $id)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-om')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = PenawaranProenergi::findOrFail($id);
        $request->validate(['catatan' => 'nullable|string']);

        $result = (new ApprovePenawaranOmAction())->execute($penawaran, $request->user()->id, $request->catatan, $this->detailUrl($penawaran->id_penawaran));

        return response()->json(['message' => $result['message']]);
    }

    public function tolakom(Request $request, $id)
    {
        if ($request->user()->cant('penawaran.proenergi.verify-om')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $penawaran = PenawaranProenergi::with(['customer', 'cabang'])->findOrFail($id);
        $request->validate(['catatan' => 'required|string']);

        $result = (new RejectPenawaranOmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']]);
    }

    public function previewPdfMultiLang(Request $request, $id)
    {
        $lang = strtolower($request->query('lang', 'id'));
        $priceDetail = $request->query('price_format') === 'detail';

        return (new GeneratePenawaranPdfAction())->execute(
            PenawaranProenergi::class,
            (int) $id,
            $lang,
            $priceDetail,
            'penawaran.pdf_proenergi',
            public_path('images/logo-proenergi.png')
        );
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
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return (float)$s;
        }
        if (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }
        return (float)$s;
    }

    private function generateNumericCode(int $length = 8): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('',  $length, '9');
        return (string) random_int($min, $max);
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
        $name = "{$safe}_" . now()->format('YmdHis') . ".png";

        Storage::disk('public')->put("$dir/$name", $pngBinary);

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

        Storage::disk('public')->put("$dir/$name", $svg);

        $abs = public_path("storage/$dir/$name");
        return [
            'abs_for_pdf' => 'file://' . $abs,
            'url'         => asset("storage/$dir/$name"),
            'rel'         => "$dir/$name",
            'svg'         => $svg,
        ];
    }

    private function deleteQrFiles(int $idPenawaran, ?string $qrUrl = null): void
    {
        if (!empty($qrUrl)) {
            $path = parse_url($qrUrl, PHP_URL_PATH);
            if ($path && str_starts_with($path, '/storage/')) {
                $rel = ltrim(substr($path, strlen('/storage/')), '/');
                Storage::disk('public')->delete($rel);
            }
        }

        $prefix = \Illuminate\Support\Str::slug("penawaran-{$idPenawaran}");
        $all = Storage::disk('public')->allFiles('qrcodes');
        foreach ($all as $file) {
            $base = basename($file);
            if (str_starts_with($base, $prefix) && (str_ends_with($base, '.png') || str_ends_with($base, '.svg'))) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /* Section: Email helpers */
    private function detailUrl(int $idPenawaran): string
    {
        return url("/penawarans-proenergi/{$idPenawaran}");
    }

    private function bmVerificationUrl(int $idPenawaran): string
    {
        return url("/penawarans-proenergi/{$idPenawaran}/verifikasi");
    }

    private function omVerificationUrl(int $idPenawaran): string
    {
        return url("/penawarans-proenergi/verifikasi/om/{$idPenawaran}");
    }
}
