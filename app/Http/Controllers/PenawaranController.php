<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Penawaran;
use App\Http\Requests\Penawaran\StorePenawaranRequest;
use App\Http\Requests\Penawaran\UpdatePenawaranRequest;
use Illuminate\Http\Request;
use App\Services\QrCodeService;
use App\Actions\Penawaran\CreatePenawaranAction;
use App\Actions\Penawaran\UpdatePenawaranAction;
use App\Actions\Penawaran\SubmitPenawaranAction;
use App\Actions\Penawaran\ApprovePenawaranBmAction;
use App\Actions\Penawaran\RejectPenawaranBmAction;
use App\Actions\Penawaran\ApprovePenawaranOmAction;
use App\Actions\Penawaran\RejectPenawaranOmAction;
use App\Actions\Penawaran\ResolvePenawaranQueueAction;
use App\Actions\Penawaran\GeneratePenawaranPdfAction;
use App\Enums\ProductPriceCogsBasis;
use App\Support\ProductPrice\ActivePriceForPeriodQuery;
use App\Support\Approval\PenawaranApprovalStepsBuilder;

class PenawaranController extends Controller
{
    private function resolvePenawaran(string $brand, int $id, array $with = []): Penawaran
    {
        return Penawaran::where('brand', $brand)->with($with)->findOrFail($id);
    }

    private function verificationGatePermissions(string $brand, string $step): array
    {
        return $brand === 'proenergi'
            ? ['penawaran.proenergi.verify', "penawaran.proenergi.verify-{$step}"]
            : ['penawaran.verify', "penawaran.verify-{$step}"];
    }

    public function index(Request $request, string $brand)
    {
        $user = $request->user();
        $permissionAny = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        $permissionOwn = $brand === 'proenergi' ? 'penawaran.proenergi.viewOwn' : 'penawaran.viewOwn';

        if ($user->cant($permissionAny) && $user->cant($permissionOwn)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Penawaran::where('brand', $brand)
            ->with(['customer', 'cabang', 'items.produk.jenis', 'items.produk.ukuran.satuan'])
            ->withSum('items as total_volume', 'volume_order');

        if ($user->cant($permissionAny)) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('customerContact', fn($cq) => $cq->where('full_name', 'like', "%{$search}%"));
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($data);
    }

    public function show(Request $request, $id, string $brand)
    {
        $penawaran = $this->resolvePenawaran($brand, (int) $id, [
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
        ]);

        $user = $request->user();
        $permissionAny = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        $permissionOwn = $brand === 'proenergi' ? 'penawaran.proenergi.viewOwn' : 'penawaran.viewOwn';
        $allowed = $user->can($permissionAny)
            || ($user->can($permissionOwn) && (int) $penawaran->user_id === (int) $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $hargaByProduk = (new ActivePriceForPeriodQuery())->forProducts(
            $penawaran->items->pluck('id_produk')->unique()->values()->all(),
            $penawaran->id_cabang,
            $penawaran->masa_berlaku
        );

        foreach ($penawaran->items as $item) {
            $itemHarga = $hargaByProduk->get($item->id_produk);
            $item->cogs_price = $itemHarga->cogs_price ?? null;
            $item->cogs_basis = $itemHarga->cogs_basis ?? null;
        }

        $payload = $penawaran->makeHidden('documentApprovals')->toArray();
        $payload['approval_attempts'] = app(PenawaranApprovalStepsBuilder::class)->buildAttempts($penawaran);

        return response()->json($payload);
    }

    public function store(StorePenawaranRequest $request, string $brand)
    {
        $result = (new CreatePenawaranAction())->execute($request->validated(), $brand, $request->user());

        if (array_key_exists('error', $result)) {
            return response()->json(['message' => $result['message'], 'error' => $result['error']], $result['status']);
        }

        return response()->json($result['penawaran'], $result['status']);
    }

    public function update(UpdatePenawaranRequest $request, $id, string $brand)
    {
        $penawaran = $this->resolvePenawaran($brand, (int) $id);

        $result = (new UpdatePenawaranAction())->execute($penawaran, $request->validated(), $request->user());

        if (array_key_exists('error', $result)) {
            return response()->json(['message' => $result['message'], 'error' => $result['error']], $result['status']);
        }

        return response()->json($result['penawaran']);
    }

    public function destroy(Request $request, $id, string $brand)
    {
        $penawaran = $this->resolvePenawaran($brand, (int) $id);

        $user = $request->user();
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.manage' : 'penawaran.manage';
        $allowed = $user->can($permission) && (int) $penawaran->user_id === (int) $user->id;

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $idPenawaran = (int) $penawaran->id_penawaran;
        $qrUrl = $penawaran->qr_code;

        try {
            (new QrCodeService())->deleteFiles($idPenawaran, $qrUrl);
        } catch (\Throwable $e) {
            report($e);
        }

        $penawaran->delete();

        return response()->json(null, 204);
    }

    public function ajukan($id, string $brand)
    {
        $penawaran = $this->resolvePenawaran($brand, (int) $id, ['customer', 'cabang']);

        $result = (new SubmitPenawaranAction())->execute(
            $penawaran,
            $this->bmVerificationUrl($brand, $penawaran->id_penawaran)
        );

        $response = ['message' => $result['message']];
        if (array_key_exists('error', $result)) {
            $response['error'] = $result['error'];
        }

        return response()->json($response, $result['status'] ?? 200);
    }

    public function bmVerificationIndex(Request $request, string $brand)
    {
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        if ($request->user()->cant($permission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = (new ResolvePenawaranQueueAction())->execute(
            $brand,
            'bm',
            $request->query('search'),
            (int) $request->query('per_page', 10)
        );

        return response()->json($data);
    }

    public function verifikasi(Request $request, $id, string $brand)
    {
        foreach ($this->verificationGatePermissions($brand, 'bm') as $permission) {
            if ($request->user()->cant($permission)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $penawaran = $this->resolvePenawaran($brand, (int) $id, ['customer', 'cabang']);
        $request->validate(['catatan' => 'nullable|string']);

        $result = (new ApprovePenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $request->user()->name ?? 'BM',
            $this->detailUrl($brand, $penawaran->id_penawaran),
            $this->omVerificationUrl($brand, $penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']], $result['status'] ?? 200);
    }

    public function tolakbm(Request $request, $id, string $brand)
    {
        foreach ($this->verificationGatePermissions($brand, 'bm') as $permission) {
            if ($request->user()->cant($permission)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $penawaran = $this->resolvePenawaran($brand, (int) $id, ['customer', 'cabang']);
        $request->validate(['catatan' => 'required|string']);

        $result = (new RejectPenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($brand, $penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']]);
    }

    public function omVerificationIndex(Request $request, string $brand)
    {
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.viewAny' : 'penawaran.viewAny';
        if ($request->user()->cant($permission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = (new ResolvePenawaranQueueAction())->execute(
            $brand,
            'om',
            $request->query('search'),
            (int) $request->query('per_page', 10)
        );

        return response()->json($data);
    }

    public function verifikasiOm(Request $request, $id, string $brand)
    {
        foreach ($this->verificationGatePermissions($brand, 'om') as $permission) {
            if ($request->user()->cant($permission)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $penawaran = $this->resolvePenawaran($brand, (int) $id);
        $request->validate(['catatan' => 'nullable|string']);

        $result = (new ApprovePenawaranOmAction())->execute($penawaran, $request->user()->id, $request->catatan, $this->detailUrl($brand, $penawaran->id_penawaran));

        return response()->json(['message' => $result['message']]);
    }

    public function tolakom(Request $request, $id, string $brand)
    {
        foreach ($this->verificationGatePermissions($brand, 'om') as $permission) {
            if ($request->user()->cant($permission)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $penawaran = $this->resolvePenawaran($brand, (int) $id, ['customer', 'cabang']);
        $request->validate(['catatan' => 'required|string']);

        $result = (new RejectPenawaranOmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($brand, $penawaran->id_penawaran)
        );

        return response()->json(['message' => $result['message']]);
    }

    public function previewPdfMultiLang(Request $request, $id, string $brand)
    {
        $lang = strtolower($request->query('lang', 'id'));
        $priceDetail = $request->query('price_format') === 'detail';

        $viewPrefix = $brand === 'proenergi' ? 'penawaran.pdf_proenergi' : 'penawaran.pdf';
        $logoLeftPath = $brand === 'proenergi'
            ? public_path('images/logo-proenergi.png')
            : public_path('images/logo-new.png');

        return (new GeneratePenawaranPdfAction())->execute(
            $brand,
            (int) $id,
            $lang,
            $priceDetail,
            $viewPrefix,
            $logoLeftPath
        );
    }

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

    private function detailUrl(string $brand, int $idPenawaran): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/{$idPenawaran}");
    }

    private function bmVerificationUrl(string $brand, int $idPenawaran): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/{$idPenawaran}/verifikasi");
    }

    private function omVerificationUrl(string $brand, int $idPenawaran): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/verifikasi/om/{$idPenawaran}");
    }
}
