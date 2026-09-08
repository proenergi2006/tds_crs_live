<?php

namespace App\Http\Controllers;

use App\Models\Penawaran;
use App\Http\Requests\Penawaran\StorePenawaranRequest;
use App\Http\Requests\Penawaran\UpdatePenawaranRequest;
use App\Http\Resources\PenawaranIndexResource;
use App\Http\Resources\PenawaranDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
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

    private function authorizeVerificationStep(Request $request, string $brand, string $step): void
    {
        foreach ($this->verificationGatePermissions($brand, $step) as $permission) {
            if ($request->user()->cant($permission)) {
                throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
            }
        }
    }

    private function prepareVerificationRequest(Request $request, $id, string $brand, string $step, string $catatanRule): Penawaran
    {
        $this->authorizeVerificationStep($request, $brand, $step);
        $penawaran = $this->resolvePenawaran($brand, (int) $id);
        $request->validate(['catatan' => $catatanRule]);

        return $penawaran;
    }

    private function verificationResponse(array $result)
    {
        $payload = ['message' => $result['message']];
        if (isset($result['error'])) {
            $payload['error'] = $result['error'];
        }

        return response()->json($payload, $result['status'] ?? 200);
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
            ->with(['customer', 'user', 'items.produk.jenis', 'items.produk.ukuran'])
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

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $paginator->through(fn ($penawaran) => (new PenawaranIndexResource($penawaran))->resolve());

        return response()->json($paginator);
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
            'items.productPrice',
            'items.sourceCabang',
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

        return response()->json((new PenawaranDetailResource($penawaran))->resolve());
    }

    public function store(StorePenawaranRequest $request, string $brand)
    {
        try {
            $penawaran = (new CreatePenawaranAction())->execute($request->validated(), $brand, $request->user());

            return response()->json($penawaran, 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal menyimpan penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdatePenawaranRequest $request, $id, string $brand)
    {
        $penawaran = $this->resolvePenawaran($brand, (int) $id);

        try {
            $updated = (new UpdatePenawaranAction())->execute($penawaran, $request->validated(), $request->user());

            return response()->json($updated);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal update penawaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
            $this->verificationUrl($brand, $penawaran->id_penawaran, 'bm')
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
        $penawaran = $this->prepareVerificationRequest($request, $id, $brand, 'bm', 'nullable|string');

        $result = (new ApprovePenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $request->user()->name ?? 'BM',
            $this->detailUrl($brand, $penawaran->id_penawaran),
            $this->verificationUrl($brand, $penawaran->id_penawaran, 'om')
        );

        return $this->verificationResponse($result);
    }

    public function tolakbm(Request $request, $id, string $brand)
    {
        $penawaran = $this->prepareVerificationRequest($request, $id, $brand, 'bm', 'required|string');

        $result = (new RejectPenawaranBmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($brand, $penawaran->id_penawaran)
        );

        return $this->verificationResponse($result);
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
        $penawaran = $this->prepareVerificationRequest($request, $id, $brand, 'om', 'nullable|string');

        $result = (new ApprovePenawaranOmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($brand, $penawaran->id_penawaran)
        );

        return $this->verificationResponse($result);
    }

    public function tolakom(Request $request, $id, string $brand)
    {
        $penawaran = $this->prepareVerificationRequest($request, $id, $brand, 'om', 'required|string');

        $result = (new RejectPenawaranOmAction())->execute(
            $penawaran,
            $request->user()->id,
            $request->catatan,
            $this->detailUrl($brand, $penawaran->id_penawaran)
        );

        return $this->verificationResponse($result);
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

    private function detailUrl(string $brand, int $idPenawaran): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/{$idPenawaran}");
    }

    private function verificationUrl(string $brand, int $idPenawaran, string $role): string
    {
        $prefix = $brand === 'proenergi' ? 'penawarans-proenergi' : 'penawarans';
        return url("/{$prefix}/verifikasi/{$role}/{$idPenawaran}");
    }
}
