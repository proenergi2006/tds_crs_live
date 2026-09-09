<?php

namespace App\Http\Controllers;

use App\Actions\SalesConfirmation\DecideSalesConfirmationAction;
use App\Actions\SalesConfirmation\SaveSalesConfirmationAction;
use App\Http\Requests\SalesConfirmation\SaveSalesConfirmationRequest;
use App\Models\PoCustomer;
use App\Models\Penawaran;
use App\Enums\SalesConfirmationStatus;

use App\Models\CustomerAdminArnya;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Models\PoCustomerPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;


class PoCustomerController extends Controller
{
    private const ROLE_BM = 8;
    private const ROLE_ADMIN_FINANCE = 9;
    private const PENAWARAN_STATUS_APPROVED_OM = 'approved_om';

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('penawaran.viewAny') && $request->user()->cant('penawaran.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = min((int) $request->query('per_page', 10), 100);
        $search  = $request->query('search');

        $query = PoCustomer::with(['customer:id_customer,customer_code,company_name', 'penawaran', 'salesConfirmation']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_poc', 'ilike', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_time', 'desc')->paginate($perPage);

        $data->getCollection()->each(function (PoCustomer $po) {
            $po->setAttribute('status_key', $po->status_key);
            $po->setAttribute('status_label', $po->status_label);
        });

        return response()->json($data);
    }
    

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('penawaran.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'id_customer'      => 'required|exists:customers,id_customer',
            'id_penawaran'     => 'required|exists:penawarans,id_penawaran',
            'nomor_poc'        => 'required|string|max:50',
            'tanggal_poc'      => 'required|date',
            'supply_date'      => 'required|date',
            'volume_poc'       => 'required|integer',
            'produk_poc'       => 'nullable|integer',
            'lampiran_poc'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'top_poc'          => 'required|string|max:20',
        ]);

        $penawaran = Penawaran::find($validated['id_penawaran']);
        if ($penawaran?->status !== self::PENAWARAN_STATUS_APPROVED_OM) {
            throw ValidationException::withMessages([
                'id_penawaran' => ['Sales Order hanya bisa dibuat dari Penawaran yang sudah disetujui OM.'],
            ]);
        }

        $data = Arr::except($validated, ['lampiran_poc']);

        $data['harga_poc'] = (float) ($penawaran->harga_dasar ?? 0) + (float) ($penawaran->oat ?? 0);

        if ($request->hasFile('lampiran_poc')) {
            $file = $request->file('lampiran_poc');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('lampiran_po', $filename, 'public');

            $data['lampiran_poc'] = $path;
            $data['lampiran_poc_ori'] = $file->getClientOriginalName();
        }

        $data['created_time'] = now();
        $data['created_by'] = auth()->user()->name ?? 'system';
        $data['created_ip'] = $request->ip();

        $po = PoCustomer::create($data);

        return response()->json(['success' => true, 'data' => $po]);
    }



    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        $po = PoCustomer::with(['customer', 'penawaran', 'salesConfirmation'])->findOrFail($id);
        $po->append(['status_key', 'status_label']);
        return response()->json($po);
    }

    public function destroy($id)
    {
        $po = PoCustomer::findOrFail($id);

        if ($po->lampiran_poc && Storage::disk('public')->exists($po->lampiran_poc)) {
            Storage::disk('public')->delete($po->lampiran_poc);
        }

        $po->delete();

        return response()->json(['success' => true, 'message' => 'PO Customer deleted']);
    }

    public function salesConfirmation(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->user()->cant('sales-confirmation.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage  = min((int) $request->query('per_page', 25), 100);
        $search   = trim((string) $request->query('search', ''));
        $scFilter = $request->query('disposisi');

        $query = PoCustomer::query()
            ->with([
                'customer:id_customer,customer_code,company_name',
                'penawaran',
                'salesConfirmation:id,po_customer_id,disposisi,lastupdate_time,created_time',
            ]);

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('nomor_poc', 'ILIKE', $like)
                  ->orWhereHas('customer', fn($qc) => $qc
                        ->where('company_name', 'ILIKE', $like)
                        ->orWhere('customer_code', 'ILIKE', $like))
                  ->orWhereHas('penawaran', fn($qp) => $qp
                        ->where('nomor_penawaran', 'ILIKE', $like));
            });
        }

        if ($scFilter !== null && $scFilter !== '') {
            $query->whereHas('salesConfirmation', function ($q) use ($scFilter) {
                $q->where('disposisi', (int) $scFilter);
            });
        }

        $data = $query->orderByDesc('tanggal_poc')
                      ->orderByDesc('created_time')
                      ->paginate($perPage);

        $data->getCollection()->transform(function ($po) {
            $sc = $po->salesConfirmation;

            $disposisi = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
            $label     = SalesConfirmationStatus::tryFrom($disposisi)?->label() ?? 'Draft';
            $when = $sc->lastupdate_time ?? $sc->created_time ?? $po->created_time;

            $marketing = $po->penawaran->marketing_name
                ?? $po->penawaran->marketing
                ?? $po->penawaran->created_by
                ?? ($po->created_by ?? '-');

            return [
                'id_poc'         => $po->id_poc,
                'nomor_poc'      => $po->nomor_poc,
                'tanggal_poc'    => $po->tanggal_poc,
                'volume_poc'     => (float) ($po->volume_poc ?? 0),
                'harga_poc'      => (float) ($po->harga_poc ?? 0),
                'customer'       => [
                    'customer_code' => $po->customer->customer_code ?? null,
                    'company_name'  => $po->customer->company_name ?? '-',
                ],
                'penawaran'      => [
                    'nomor_penawaran' => $po->penawaran->nomor_penawaran ?? '-',
                ],
                'marketing_name' => $marketing,

                'disposisi'      => $disposisi,
                'disposisi_text' => $label,
                'disposisi_time' => $when ? Carbon::parse($when)->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json($data);
    }


public function showSalesConfirmation(int $idPoc): \Illuminate\Http\JsonResponse
{
    if (auth()->user()->cant('sales-confirmation.manage')) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $po = PoCustomer::with(['customer', 'penawaran'])->findOrFail($idPoc);

    $arnya = CustomerAdminArnya::firstOrCreate(
        ['id_customer' => $po->id_customer],
        ['not_yet'=>0,'ov_up_07'=>0,'ov_under_30'=>0,'ov_under_60'=>0,'ov_under_90'=>0,'ov_up_90'=>0]
    );

    $sc  = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();
    $apr = $sc ? SalesConfirmationApproval::where('id_sales', $sc->id)->first() : null;

    $scDisp  = (int) ($sc?->getRawOriginal('disposisi') ?? SalesConfirmationStatus::PendingAdmin->value);
    $scLabel = SalesConfirmationStatus::tryFrom($scDisp)?->label() ?? 'Draft';
    $scWhen = $sc->lastupdate_time
        ?? $sc->created_time
        ?? $po->created_time
        ?? now();

    $marketing = $po->penawaran?->marketing_name
        ?? $po->penawaran?->marketing
        ?? $po->penawaran?->created_by
        ?? ($po->created_by ?? '-');

    return response()->json([
        'poc' => [
            'id_poc'      => $po->id_poc,
            'nomor_poc'   => $po->nomor_poc,
            'tanggal_poc' => $po->tanggal_poc,
            'supply_date' => $po->supply_date,
            'volume_poc'  => (float)($po->volume_poc ?? 0),
            'harga_poc'   => (float)($po->harga_poc ?? 0),
        ],
        'customer' => [
            'id_customer'   => $po->customer?->id_customer,
            'customer_code' => $po->customer?->customer_code,
            'company_name'  => $po->customer?->company_name,
            'credit_limit'  => (float) ($po->customer?->current_credit_limit ?? 0),
        ],
        'penawaran' => [
            'nomor_penawaran' => $po->penawaran?->nomor_penawaran ?? '-',
            'marketing_name'  => $marketing,
            'top'             => $po->top_poc ?? null,
        ],
        'arnya' => [
            'id_arnya'     => $arnya->id_arnya,
            'id_customer'  => $arnya->id_customer,
            'not_yet'      => (float)$arnya->not_yet,
            'ov_up_07'     => (float)$arnya->ov_up_07,
            'ov_under_30'  => (float)$arnya->ov_under_30,
            'ov_under_60'  => (float)$arnya->ov_under_60,
            'ov_under_90'  => (float)$arnya->ov_under_90,
            'ov_up_90'     => (float)$arnya->ov_up_90,
        ],
        'sc' => [
            'disposisi'       => $scDisp,
            'disposisi_label' => $scLabel,
            'disposisi_time'  => Carbon::parse($scWhen)->format('Y-m-d H:i:s'),
        ],
        'sc_header' => $sc ? [
            'credit_limit'    => (float)$sc->credit_limit,
            'not_yet'         => (float)$sc->not_yet,
            'ov_up_07'        => (float)$sc->ov_up_07,
            'ov_under_30'     => (float)$sc->ov_under_30,
            'ov_under_60'     => (float)$sc->ov_under_60,
            'ov_under_90'     => (float)$sc->ov_under_90,
            'ov_up_90'        => (float)$sc->ov_up_90,
            'po_status'       => $sc->po_status,
            'po_volume'       => (float)$sc->po_volume,
            'po_amount'       => (float)$sc->po_amount,
            'reminding'       => $sc->reminding,
            'proposed_status' => (int)$sc->proposed_status,
            'add_top'         => (int)$sc->add_top,
            'add_cl'          => (int)$sc->add_cl,
            'type_customer'   => $sc->type_customer,
            'customer_amount' => (float)$sc->customer_amount,
            'customer_date'   => $sc->customer_date,
            'lampiran_unblock'     => $sc->lampiran_unblock,
            'lampiran_unblock_ori' => $sc->lampiran_unblock_ori,
        ] : null,
        'approval' => $apr ? [
            'adm_result'      => (int)$apr->adm_result,
            'adm_summary'     => $apr->adm_summary,
            'adm_result_date' => $apr->adm_result_date,
            'adm_pic'         => $apr->adm_pic,

            'bm_result'       => (int)$apr->bm_result,
            'bm_summary'      => $apr->bm_summary,
            'bm_result_date'  => $apr->bm_result_date,
            'bm_pic'          => $apr->bm_pic,
        ] : null,
    ]);
}

public function saveSalesConfirmation(SaveSalesConfirmationRequest $request, int $idPoc, SaveSalesConfirmationAction $action): \Illuminate\Http\JsonResponse
{
    $user = $request->user();
    if ($user->cant('sales-confirmation.manage') || (int) $user->primary_role_id !== self::ROLE_ADMIN_FINANCE) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $po = PoCustomer::with('customer:id_customer')->findOrFail($idPoc);

    $sc = $action->execute($po, $request->validated(), $request->file('lampiran_unblock'), $request->user()->name ?? 'system');

    return response()->json(['success' => true, 'id_sales_confirmation' => $sc->id]);
}

public function saveSalesConfirmationBM(Request $request, int $idPoc, DecideSalesConfirmationAction $action): \Illuminate\Http\JsonResponse
{
    if ($request->user()->cant('sales-confirmation.manage') || (int) $request->user()->primary_role_id !== self::ROLE_BM) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $data = $request->validate([
        'bm_result'  => 'required|in:1,2',
        'bm_summary' => 'nullable|string',
    ]);

    $po = PoCustomer::findOrFail($idPoc);
    $sc = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();

    if (!$sc) {
        return response()->json(['message' => 'Sales Confirmation belum dibuat oleh Admin.'], 404);
    }

    if (SalesConfirmationStatus::tryFrom((int) $sc->getRawOriginal('disposisi')) !== SalesConfirmationStatus::PendingBm) {
        return response()->json(['message' => 'Sales Confirmation tidak dalam status Menunggu BM.'], 409);
    }

    $sc = $action->execute($sc, (int) $data['bm_result'], $data['bm_summary'] ?? null, $request->user()->name ?? 'system');

    return response()->json([
        'success'         => true,
        'disposisi'       => $sc->disposisi->value,
        'disposisi_label' => $sc->disposisi->label(),
        'flag_approval'   => (int) $sc->flag_approval,
    ]);
}


public function updateNomorPo(Request $r, $idPoc){
    $r->validate(['nomor_poc' => 'required|string|max:50']);
    $po = \App\Models\PoCustomer::findOrFail($idPoc);
    $po->nomor_poc = $r->nomor_poc;
    $po->save();
    return response()->json(['success'=>true]);
}

public function getPoPlan($idPoc)
    {
        $po = PoCustomer::with(['customer','penawaran'])->findOrFail($idPoc);

        $p = $po->penawaran;
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
            'customer_name'        => $po->customer->nama_perusahaan ?? '-',
            'top'                  => $po->top_poc ?? ($p->top ?? '-'),
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
                    // po_customer_plan gak punya kolom alamat, alamat/catatan nebeng di status_jadwal
                    'address'      => $r->status_jadwal,
                    'volume_liter' => (int)$r->volume_kirim,
                    'real_liter'   => (int)$r->realisasi_kirim,
                    'status'       => $statusMap[$r->status_plan] ?? '-',
                    'notes'        => $r->catatan_reschedule,
                ];
            });

        return response()->json(['header'=>$header, 'items'=>$items]);
    }

    public function createPoPlan(Request $request, $idPoc)
    {
        $request->validate([
            'ship_date'    => 'required|date',
            'address'      => 'nullable|string',
            'volume_kirim' => 'required|integer|min:1',
            'notes'        => 'nullable|string',
            'is_urgent'    => 'nullable|boolean',
        ]);

        $po = PoCustomer::findOrFail($idPoc);

        $plan = PoCustomerPlan::create([
            'id_poc'           => $po->id_poc,
            'id_lcr'           => 0,
            'tanggal_kirim'    => $request->ship_date,
            'volume_kirim'     => (int)$request->volume_kirim,
            'realisasi_kirim'  => 0,
            'is_urgent'        => $request->boolean('is_urgent') ? 1 : 0,
            'status_plan'      => 0,
            // po_customer_plan gak punya kolom alamat, alamat + catatan nebeng di status_jadwal
            'status_jadwal'    => trim(($request->address ? "Alamat: ".$request->address."\n" : '') . ($request->notes ?? '')),
            'catatan_reschedule' => null,
            'ask_approval'     => 0,
            'is_approved'      => 1,
            'created_time'     => now(),
            'created_ip'       => $request->ip(),
            'created_by'       => auth()->user()->name ?? 'system',
        ]);

        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return response()->json([
            'success' => true,
            'id'      => $plan->id_plan,
            'header_patch' => [
                'shipped_liter'        => (int)$shipped,
                'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
            ],
        ]);
    }

    public function deletePoPlan($idPoc, $id)
    {
        PoCustomerPlan::where('id_poc', $idPoc)->where('id_plan', $id)->delete();

        $po = PoCustomer::findOrFail($idPoc);
        $shipped = PoCustomerPlan::where('id_poc', $po->id_poc)->sum('realisasi_kirim');

        return response()->json([
            'success' => true,
            'header_patch' => [
                'shipped_liter'        => (int)$shipped,
                'book_remaining_liter' => max(0, (int)($po->volume_poc ?? 0) - (int)$shipped),
            ],
        ]);
    }

}

