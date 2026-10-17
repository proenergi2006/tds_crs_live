<?php

namespace App\Http\Controllers\PoCustomer;

use App\Actions\PoCustomer\CreatePoCustomerAction;
use App\Actions\PoCustomer\DeletePoCustomerAction;
use App\Actions\PoCustomer\UpdatePoCustomerAction;
use App\Enums\QuotationBrand;
use App\Http\Controllers\Controller;
use App\Http\Requests\PoCustomer\StorePoCustomerRequest as StoreRequest;
use App\Http\Requests\PoCustomer\UpdatePoCustomerRequest as UpdateRequest;
use App\Http\Resources\PoCustomerDetailResource;
use App\Http\Resources\PoCustomerIndexResource;
use App\Http\Responses\ApiResponse;
use App\Models\PoCustomer;
use App\Models\PoCustomerPlan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoCustomerController extends Controller
{
    private const DETAIL_RELATIONS = [
        'customer:id_customer,customer_code,company_name',
        'quotation',
        'items.quotationItem.product.ukuran.satuan',
        'items.quotationItem.sourceBranch',
        'salesConfirmation',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PoCustomer::class);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search'   => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $query = PoCustomer::with([
            'customer:id_customer,customer_code,company_name',
            'quotation:id,nomor_penawaran,brand,user_id',
            'salesConfirmation:id,po_customer_id,disposisi',
        ])->withOrderAggregates();

        if (! PoCustomer::readsAllPosAs($user)) {
            $brands = QuotationBrand::viewableAnyBy($user);

            $query->whereHas('quotation', fn (Builder $q) => $q->where(
                fn (Builder $w) => $w->whereIn('brand', $brands)->orWhere('user_id', $user->id)
            ));
        }

        if (! empty($validated['search'])) {
            $query->where('nomor_poc', 'ilike', "%{$validated['search']}%");
        }

        $paginator = $query->orderBy('created_time', 'desc')->paginate($validated['per_page'] ?? 10);

        return ApiResponse::success(
            PoCustomerIndexResource::collection($paginator),
            'Daftar PO Customer berhasil dimuat.'
        );
    }

    public function store(StoreRequest $request, CreatePoCustomerAction $action): JsonResponse
    {
        $po = $action->execute(
            $request->validated(),
            $request->file('lampiran_poc'),
            $request->user(),
            $request->ip()
        );

        return ApiResponse::success(
            new PoCustomerDetailResource($po->load(self::DETAIL_RELATIONS)),
            'PO Customer berhasil dibuat.',
            201
        );
    }

    public function update(
        UpdateRequest $request,
        PoCustomer $customerPo,
        UpdatePoCustomerAction $action
    ): JsonResponse {
        $po = $action->execute(
            $customerPo,
            $request->validated(),
            $request->file('lampiran_poc'),
            $request->user()->name ?? 'system',
            $request->ip()
        );

        return ApiResponse::success(
            new PoCustomerDetailResource($po->load(self::DETAIL_RELATIONS)),
            'PO Customer berhasil diperbarui.'
        );
    }

    public function show(PoCustomer $customerPo): JsonResponse
    {
        $this->authorize('view', $customerPo);

        return ApiResponse::success(
            new PoCustomerDetailResource($customerPo->load(self::DETAIL_RELATIONS)),
            'Detail PO Customer berhasil dimuat.'
        );
    }

    public function destroy(PoCustomer $customerPo, DeletePoCustomerAction $action): JsonResponse
    {
        $this->authorize('delete', $customerPo);

        $action->execute($customerPo);

        return ApiResponse::success(
            null,
            'PO Customer berhasil dihapus.'
        );
    }

    public function updateNomorPo(Request $r, PoCustomer $poc): JsonResponse
    {
        $this->authorize('update', $poc);

        $po = $poc->load('quotation');

        $r->validate(['nomor_poc' => 'required|string|max:50']);

        if ($po->is_locked) {
            return ApiResponse::error(
                'Nomor PO tidak bisa diubah karena PO sudah masuk proses Sales Confirmation.',
                409
            );
        }

        $po->nomor_poc = $r->nomor_poc;
        $po->save();
        return ApiResponse::success(
            null,
            'Nomor PO berhasil diperbarui.'
        );
    }

    public function getPoPlan(PoCustomer $poc): JsonResponse
    {
        $this->authorize('view', $poc);

        $po = $poc->load(['customer','quotation']);

        $p = $po->quotation;
        $awal  = $p->periode_awal  ?? $p->start_date ?? $p->tanggal_mulai ?? null;
        $akhir = $p->periode_akhir ?? $p->end_date   ?? $p->tanggal_selesai ?? null;
        $periode = ($awal && $akhir)
            ? Carbon::parse($awal)->format('d/m/Y').' - '.Carbon::parse($akhir)->format('d/m/Y')
            : '-';

        $volume = (int)($po->volume_poc ?? 0);
        $harga  = (float)($po->harga_poc ?? 0);

        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        $header = [
            'doc_code'             => 'PO-'.str_pad($po->id_poc, 5, '0', STR_PAD_LEFT),
            'penawaran_code'       => $p->nomor_penawaran ?? '-',
            'periode'              => $periode,
            'customer_name'        => $po->customer->company_name ?? '-',
            'tipe_bayar_label'     => $po->tipe_bayar_label,
            'termin_hari'          => $po->termin_hari,
            'nomor_poc'            => $po->nomor_poc,
            'tanggal_poc'          => $po->tanggal_poc,
            'supply_date'          => $po->supply_date,
            'produk'               => $po->produk_poc ?? ($p->produk_name ?? '-'),
            'harga_per_liter'      => $harga,
            'jumlah_volume_liter'  => $volume,
            'total_order_rp'       => $volume * $harga,

            'total_liter'          => $volume,
            'shipped_liter'        => (int)$shipped,
            'book_remaining_liter' => max(0, $volume - (int)$shipped),
            'close_po_liter'       => 0,
        ];

        $items = PoCustomerPlan::where('id_poc', $po->id_poc)
            ->orderBy('created_time')
            ->get()
            ->map(function ($r) {
                $statusMap = [0=>'Terdaftar',1=>'Masuk PR',2=>'Reschedule',3=>'Pending'];
                return [
                    'id'           => $r->id_plan,
                    'issued_at'    => $r->created_time,
                    'ship_date'    => $r->tanggal_kirim,
                    'address'      => $r->status_jadwal,
                    'volume_liter' => (int)$r->volume_kirim,
                    'real_liter'   => (int)$r->realisasi_kirim,
                    'status'       => $statusMap[$r->status_plan] ?? '-',
                    'notes'        => $r->catatan_reschedule,
                ];
            });

        return ApiResponse::success(
            ['header' => $header, 'items' => $items],
            'Plan pengiriman PO berhasil dimuat.'
        );
    }

    public function createPoPlan(Request $request, PoCustomer $poc): JsonResponse
    {
        $this->authorize('update', $poc);

        $request->validate([
            'ship_date'    => 'required|date',
            'address'      => 'nullable|string',
            'volume_kirim' => 'required|integer|min:1',
            'notes'        => 'nullable|string',
            'is_urgent'    => 'nullable|boolean',
        ]);

        $po = $poc;

        $plan = PoCustomerPlan::create([
            'id_poc'           => $po->id_poc,
            'id_lcr'           => 0,
            'tanggal_kirim'    => $request->ship_date,
            'volume_kirim'     => (int)$request->volume_kirim,
            'realisasi_kirim'  => 0,
            'is_urgent'        => $request->boolean('is_urgent') ? 1 : 0,
            'status_plan'      => 0,
            'status_jadwal'    => trim(($request->address ? "Alamat: ".$request->address."\n" : '') . ($request->notes ?? '')),
            'catatan_reschedule' => null,
            'ask_approval'     => 0,
            'is_approved'      => 1,
            'created_time'     => now(),
            'created_ip'       => $request->ip(),
            'created_by'       => auth()->user()->name ?? 'system',
        ]);

        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return ApiResponse::success(
            [
                'id'      => $plan->id_plan,
                'header_patch' => [
                    'shipped_liter'        => (int)$shipped,
                    'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
                ],
            ],
            'Plan pengiriman berhasil disimpan.',
            201
        );
    }

    public function deletePoPlan(PoCustomer $poc, PoCustomerPlan $plan): JsonResponse
    {
        $this->authorize('update', $poc);

        $plan->delete();

        $po = $poc;
        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return ApiResponse::success(
            [
                'header_patch' => [
                    'shipped_liter'        => (int)$shipped,
                    'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
                ],
            ],
            'Plan pengiriman berhasil dihapus.'
        );
    }
}
