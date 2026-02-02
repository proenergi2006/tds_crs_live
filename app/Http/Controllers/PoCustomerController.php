<?php

namespace App\Http\Controllers;

use App\Models\PoCustomer;

use App\Models\CustomerAdminArnya;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Models\PoCustomerPlan;
use App\Models\SalesColleteral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PoCustomerController extends Controller
{
    // app/Http/Controllers/PoCustomerController.php

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');
    
        $query = PoCustomer::with(['customer', 'penawaran'])
        ->whereIn('disposisi_poc', [1, 2, 3, 4, 5, 6]); // Hanya yang sudah diajukan
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_poc', 'like', "%{$search}%");
            });
        }
    
        $data = $query->orderBy('created_time', 'desc')
                      ->paginate($perPage);
    
        return response()->json($data);
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'id_customer'      => 'required|exists:customers,id_customer',
            'id_penawaran'     => 'required|exists:penawarans,id_penawaran',
            'nomor_poc'        => 'required|string|max:50',
            'tanggal_poc'      => 'required|date',
            'supply_date'      => 'required|date',
            'harga_poc'        => 'required|numeric',
            'volume_poc'       => 'required|integer',
            'produk_poc'       => 'nullable|integer',
            'lampiran_poc'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'top_poc'          => 'required|string|max:20',
        ]);
        
        $data = $request->only([
            'id_customer',
            'id_penawaran',
            'nomor_poc',
            'tanggal_poc',
            'supply_date',
            'harga_poc',
            'volume_poc',
            'produk_poc',
            'top_poc',

        ]);
    
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
        $data['disposisi_poc'] = 1;
    
        $po = \App\Models\PoCustomer::create($data);
    
        return response()->json(['success' => true, 'data' => $po]);
    }
    

    public function show($id)
    {
        $po = PoCustomer::with(['customer', 'penawaran'])->findOrFail($id);
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


    public function indexForAdmin(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');
    
        $query = PoCustomer::with(['customer', 'cabang', 'items.produk'])
        ->whereIn('disposisi_penawaran', [1, 2, 3, 4, 5, 6]); // Hanya yang sudah diajukan
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penawaran', 'like', "%{$search}%")
                  ->orWhere('kepada', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }
    
        $data = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage);
    
        return response()->json($data);
    }

    // app/Http/Controllers/PoCustomerController.php

    public function salesConfirmation(Request $request)
{
    $perPage = (int) $request->query('per_page', 25);
    $search  = trim((string) $request->query('search', ''));
    $scFilter = $request->query('disposisi');

    $query = \App\Models\PoCustomer::query()
        ->with([
            'customer:id_customer,kode_pelanggan,nama_perusahaan',
            'penawaran', // ambil semua kolom penawaran biar aman
            'salesConfirmation:id,po_customer_id,disposisi,lastupdate_time,created_time',
        ]);
        // kalau masih ada filter disposisi_poc lama, boleh dihapus agar tak ngikat POC:
        // ->whereIn('disposisi_poc', [1,2,3,4,5,6]);

    // filter teks
    if ($search !== '') {
        $like = "%{$search}%";
        $query->where(function ($q) use ($like) {
            $q->where('nomor_poc', 'ILIKE', $like)
              ->orWhereHas('customer', fn($qc) => $qc
                    ->where('nama_perusahaan', 'ILIKE', $like)
                    ->orWhere('kode_pelanggan', 'ILIKE', $like))
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

        $disposisi = (int)($sc->disposisi ?? 1);
        $label = match ($disposisi) {
            1 => 'Menunggu Verifikasi Admin',
            2 => 'Menunggu Verifikasi BM',
            3 => 'Menunggu OM',
            4 => 'Selesai',
            5 => 'Ditolak',
            default => 'Draft',
        };
        $when = $sc->lastupdate_time ?? $sc->created_time ?? $po->created_time;

        // marketing name (fallback aman)
        $marketing = $po->penawaran->marketing_name
            ?? $po->penawaran->marketing
            ?? $po->penawaran->created_by
            ?? ($po->created_by ?? '-');

        // NOTE: list ingin tampil m³ & harga per m³
        $vol_m3   = ($po->volume_poc ?? 0) / 1000;
        $harga_m3 = ($po->harga_poc ?? 0) * 1000;

        return [
            'id_poc'         => $po->id_poc,
            'nomor_poc'      => $po->nomor_poc,
            'tanggal_poc'    => $po->tanggal_poc,
            'volume_poc'     => $vol_m3,   // m³ untuk tampilan list
            'harga_poc'      => $harga_m3, // Rp per m³
            'customer'       => [
                'kode_pelanggan'  => $po->customer->kode_pelanggan ?? null,
                'nama_perusahaan' => $po->customer->nama_perusahaan ?? '-',
            ],
            'penawaran'      => [
                'nomor_penawaran' => $po->penawaran->nomor_penawaran ?? '-',
            ],
            'marketing_name' => $marketing,

            // ⬇️ ganti ke disposisi dari sales_confirmation
            'disposisi'      => $disposisi,
            'disposisi_text' => $label,
            'disposisi_time' => $when ? Carbon::parse($when)->format('Y-m-d H:i:s') : null,
        ];
    });

    return response()->json($data);
}


public function showSalesConfirmation($idPoc)
{
    // Ambil PO Customer + relasi (tanpa select kolom spesifik agar aman dari 42703)
    $po = PoCustomer::with(['customer', 'penawaran'])->findOrFail($idPoc);

    // Siapkan Aging AR dari tabel customer_admin_arnya (buat default 0 kalau belum ada)
    $arnya = CustomerAdminArnya::firstOrCreate(
        ['id_customer' => $po->id_customer],
        ['not_yet'=>0,'ov_up_07'=>0,'ov_under_30'=>0,'ov_under_60'=>0,'ov_under_90'=>0,'ov_up_90'=>0]
    );

    // Header Sales Confirmation + Approval chain
    $sc  = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();
    $apr = $sc ? SalesConfirmationApproval::where('id_sales', $sc->id)->first() : null;

    // Disposisi dari sales_confirmation (default 1 = Menunggu Verifikasi Admin)
    $scDisp  = (int)($sc->disposisi ?? 1);
    $scLabel = match ($scDisp) {
        1 => 'Menunggu Verifikasi Admin',
        2 => 'Menunggu Verifikasi BM',
        3 => 'Menunggu OM',
        4 => 'Selesai',
        5 => 'Ditolak',
        default => 'Draft',
    };
    $scWhen = $sc->lastupdate_time
        ?? $sc->created_time
        ?? $po->created_time
        ?? now();

    // Fallback nama marketing
    $marketing = optional($po->penawaran)->marketing_name
        ?? optional($po->penawaran)->marketing
        ?? optional($po->penawaran)->created_by
        ?? ($po->created_by ?? '-');

    // Siapkan payload aman (pakai null coalescing untuk kolom yang mungkin tidak ada)
    return response()->json([
        'poc' => [
            'id_poc'      => $po->id_poc,
            'nomor_poc'   => $po->nomor_poc,
            'tanggal_poc' => $po->tanggal_poc,
            'supply_date' => $po->supply_date,
            // volume & harga dari POC disimpan dalam LITER & harga per LITER (apa adanya)
            'volume_poc'  => (float)($po->volume_poc ?? 0),
            'harga_poc'   => (float)($po->harga_poc ?? 0),
        ],
        'customer' => [
            'id_customer'     => optional($po->customer)->id_customer,
            'kode_pelanggan'  => optional($po->customer)->kode_pelanggan,
            'nama_perusahaan' => optional($po->customer)->nama_perusahaan,
            'credit_limit'    => (float)(optional($po->customer)->credit_limit ?? 0),
        ],
        'penawaran' => [
            'nomor_penawaran' => optional($po->penawaran)->nomor_penawaran ?? '-',
            'marketing_name'  => $marketing,
            'top'             => $po->top_poc ?? optional($po->penawaran)->top ?? null,
        ],
        // Aging AR (ambil dari customer_admin_arnya)
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
        // Status/Disposisi dari sales_confirmation
        'sc' => [
            'disposisi'       => $scDisp,
            'disposisi_label' => $scLabel,
            'disposisi_time'  => Carbon::parse($scWhen)->format('Y-m-d H:i:s'),
        ],
        // Header SC yang diisi Admin (read-only untuk BM/OM)
        'sc_header' => $sc ? [
            'credit_limit'    => (float)$sc->credit_limit,
            'not_yet'         => (float)$sc->not_yet,
            'ov_up_07'        => (float)$sc->ov_up_07,
            'ov_under_30'     => (float)$sc->ov_under_30,
            'ov_under_60'     => (float)$sc->ov_under_60,
            'ov_under_90'     => (float)$sc->ov_under_90,
            'ov_up_90'        => (float)$sc->ov_up_90,
            'po_status'       => $sc->po_status,
            'po_volume'       => (float)$sc->po_volume,    // LITER
            'po_amount'       => (float)$sc->po_amount,
            'reminding'       => $sc->reminding,
            'proposed_status' => (int)$sc->proposed_status,
            'add_top'         => (int)$sc->add_top,
            'add_cl'          => (int)$sc->add_cl,
            'type_customer'   => $sc->type_customer,       // 1=commitment, 2=collateral, null
            'customer_amount' => (float)$sc->customer_amount,
            'customer_date'   => $sc->customer_date,
            'lampiran_unblock'     => $sc->lampiran_unblock,
            'lampiran_unblock_ori' => $sc->lampiran_unblock_ori,
        ] : null,
        // Ringkasan approval (Admin + BM) untuk ditampilkan / prefilling
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

public function saveSalesConfirmation(Request $request, $idPoc)
{
    $po = PoCustomer::with('customer:id_customer')->findOrFail($idPoc);
    $idCustomer = $po->id_customer;

    // Validasi
    $request->validate([
        // aging + CL
        'credit_limit'   => 'nullable|numeric',
        'not_yet'        => 'nullable|numeric',
        'ov_up_07'       => 'nullable|numeric',
        'ov_under_30'    => 'nullable|numeric',
        'ov_under_60'    => 'nullable|numeric',
        'ov_under_90'    => 'nullable|numeric',
        'ov_up_90'       => 'nullable|numeric',

        // PO & proposal
        'po_status'      => 'nullable|string|max:50',
        'po_volume'      => 'nullable|numeric',
        'po_amount'      => 'nullable|numeric',
        'reminding'      => 'nullable|string|max:255',
        'proposed_status'=> 'required|in:0,1',
        'add_top'        => 'nullable|in:0,1',
        'add_cl'         => 'nullable|in:0,1',

        // payment type
        'type_customer'  => 'nullable|in:1,2', // 1=Commitment, 2=Collateral
        'customer_amount'=> 'nullable|numeric',
        'customer_date'  => 'nullable|date',

        // collateral arrays (jika type_customer=2)
        'customer_amount_coll.*' => 'nullable|numeric',
        'customer_date_coll.*'   => 'nullable|date',
        'item_coll.*'            => 'nullable|string|max:255',

        // attachment (wajib jika proposed_status=1)
        'lampiran_unblock'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

        // approval admin
        'approval'        => 'required|in:0,1,2', // 1=Ya, 2=Tidak, 0=Belum
        'admin_summary'   => 'nullable|string',
        'kode_pelanggan'  => 'nullable|string|max:50',
    ]);

    // aturan: jika proposed_status=1 maka file wajib
    if ((int)$request->proposed_status === 1 && !$request->hasFile('lampiran_unblock')) {
        return response()->json(['message' => 'File Attachment Unblock wajib diupload saat Proposed.'], 422);
    }

    return DB::transaction(function () use ($request, $po, $idCustomer) {

        // 1) Upsert aging AR ke customer_admin_arnya
        CustomerAdminArnya::updateOrCreate(
            ['id_customer' => $idCustomer],
            [
                'not_yet'     => $request->not_yet ?? 0,
                'ov_up_07'    => $request->ov_up_07 ?? 0,
                'ov_under_30' => $request->ov_under_30 ?? 0,
                'ov_under_60' => $request->ov_under_60 ?? 0,
                'ov_under_90' => $request->ov_under_90 ?? 0,
                'ov_up_90'    => $request->ov_up_90 ?? 0,
            ]
        );

        // 2) Simpan header Sales Confirmation
        $sc = SalesConfirmation::updateOrCreate(
            ['po_customer_id' => $po->id_poc],
            [
                'id_customer'     => $idCustomer,
                'credit_limit'    => $request->credit_limit ?? 0,
                'not_yet'         => $request->not_yet ?? 0,
                'ov_up_07'        => $request->ov_up_07 ?? 0,
                'ov_under_30'     => $request->ov_under_30 ?? 0,
                'ov_under_60'     => $request->ov_under_60 ?? 0,
                'ov_under_90'     => $request->ov_under_90 ?? 0,
                'ov_up_90'        => $request->ov_up_90 ?? 0,
                'reminding'       => $request->reminding,
                'po_status'       => $request->po_status,
                'po_volume'       => $request->po_volume,
                'po_amount'       => $request->po_amount,
                'proposed_status' => (int)$request->proposed_status,
                'add_top'         => (int)($request->add_top ?? 0),
                'add_cl'          => (int)($request->add_cl ?? 0),
                'disposisi'       => 2, // Menunggu SM (sesuai flow lama)
                'flag_approval'   => 0,
                'role_approved'   => null,
                'tgl_approved'    => null,
                'type_customer'   => $request->type_customer,     // 1 commitment, 2 collateral
                'customer_amount' => $request->type_customer == 1 ? ($request->customer_amount ?? 0) : 0,
                'customer_date'   => $request->type_customer == 1 ? $request->customer_date : null,
                'lastupdate_by'   => auth()->user()->name ?? 'system',
                'lastupdate_time' => now(),
            ]
        );

        // 2.a) handle lampiran unblock (jika ada)
        if ($request->hasFile('lampiran_unblock')) {
            $f = $request->file('lampiran_unblock');
            $filename = $sc->id.'_'.md5($f->getClientOriginalName()).'.'.$f->getClientOriginalExtension();
            $path = $f->storeAs('lampiran_unblock', $filename, 'public');

            $sc->lampiran_unblock     = $path;
            $sc->lampiran_unblock_ori = $f->getClientOriginalName();
            $sc->save();
        }

        // 2.b) collateral items (jika type_customer=2)
        if ($request->type_customer == 2) {
            SalesColleteral::where('sales_id', $sc->id)->delete();
            $dates = $request->customer_date_coll ?? [];
            $amts  = $request->customer_amount_coll ?? [];
            $items = $request->item_coll ?? [];
            foreach ($dates as $i => $d) {
                if (!$d) continue;
                SalesColleteral::create([
                    'sales_id' => $sc->id,
                    'date'     => $d,
                    'amount'   => (float)($amts[$i] ?? 0),
                    'item'     => (string)($items[$i] ?? ''),
                ]);
            }
        } else {
            // kalau bukan collateral, hapus data collateral sebelumnya
            SalesColleteral::where('sales_id', $sc->id)->delete();
        }

        // 3) update approval admin
        SalesConfirmationApproval::updateOrCreate(
            ['id_sales' => $sc->id],
            [
                'adm_result'      => (int)$request->approval,
                'adm_summary'     => $request->admin_summary ? nl2br($request->admin_summary) : null,
                'adm_result_date' => now(),
                'adm_pic'         => auth()->user()->name ?? 'system',
                // reset hasil level lain
                'bm_result' => 0, 'bm_summary' => null, 'bm_result_date' => null, 'bm_pic' => null,
                'om_result' => 0, 'om_summary' => null, 'om_result_date' => null, 'om_pic' => null,
                'mgr_result'=> 0, 'mgr_summary'=> null, 'mgr_result_date'=> null, 'mgr_pic'=> null,
                'cfo_result'=> 0, 'cfo_summary'=> null, 'cfo_result_date'=> null, 'cfo_pic'=> null,
            ]
        );

        // 4) opsional update kode pelanggan
        if ($request->filled('kode_pelanggan')) {
            DB::table('customers')
              ->where('id_customer', $idCustomer)
              ->update(['kode_pelanggan' => $request->kode_pelanggan]);
        }

        return response()->json(['success' => true, 'id_sales_confirmation' => $sc->id]);
    });
}

public function saveSalesConfirmationBM(Request $request, $idPoc)
{
    $request->validate([
        'bm_result'  => 'required|in:1,2',   // 1=Setuju, 2=Tidak
        'bm_summary' => 'nullable|string',
    ]);

    $po = PoCustomer::findOrFail($idPoc);
    $sc = SalesConfirmation::where('po_customer_id', $po->id_poc)->first();

    if (!$sc) {
        return response()->json(['message' => 'Sales Confirmation belum dibuat oleh Admin.'], 404);
    }

    return DB::transaction(function () use ($request, $sc) {
        // 1) Update tabel approval: kolom BM saja (tidak menyentuh kolom Admin/level lain)
        SalesConfirmationApproval::updateOrCreate(
            ['id_sales' => $sc->id],
            [
                'bm_result'      => (int)$request->bm_result,
                'bm_summary'     => $request->bm_summary ? nl2br($request->bm_summary) : null,
                'bm_result_date' => now(),
                'bm_pic'         => auth()->user()->name ?? 'system',
            ]
        );

        // 2) Update header sesuai logic lama kamu
        $sc->flag_approval = (int)$request->bm_result; // 1 atau 2
        $sc->role_approved = 7;                        // id role BM
        $sc->tgl_approved  = now();

        if ((int)$request->bm_result === 2) {
            // jika BM tolak, kembalikan ke Admin
            $sc->disposisi = 1; // Menunggu Verifikasi Admin
        }
        // NOTE: jika ingin auto-lanjut ke OM saat setuju, aktifkan baris di bawah:
        // if ((int)$request->bm_result === 1) { $sc->disposisi = 3; } // Menunggu OM

        $sc->lastupdate_by   = auth()->user()->name ?? 'system';
        $sc->lastupdate_time = now();
        $sc->save();

        // 3) (opsional) kirim notifikasi/email di sini…

        return response()->json([
            'success' => true,
            'flag_approval' => $sc->flag_approval,
            'role_approved' => $sc->role_approved,
            'disposisi'     => $sc->disposisi,
        ]);
    });
} 


public function updateNomorPo(Request $r, $idPoc){
    $r->validate(['nomor_poc' => 'required|string|max:50']);
    $po = \App\Models\PoCustomer::findOrFail($idPoc);
    $po->nomor_poc = $r->nomor_poc;
    $po->save();
    return response()->json(['success'=>true]);
}

public function closePo($idPoc){
    $sc = \App\Models\SalesConfirmation::where('po_customer_id', $idPoc)->firstOrFail();
    $sc->disposisi = 4; // Selesai
    $sc->lastupdate_time = now();
    $sc->lastupdate_by = auth()->user()->name ?? 'system';
    $sc->save();
    return response()->json(['success'=>true,'disposisi'=>$sc->disposisi]);
}

public function getPoPlan($idPoc)
    {
        $po = PoCustomer::with(['customer','penawaran'])->findOrFail($idPoc);

        // periode dari penawaran
        $p = $po->penawaran;
        $awal  = $p->periode_awal  ?? $p->start_date ?? $p->tanggal_mulai ?? null;
        $akhir = $p->periode_akhir ?? $p->end_date   ?? $p->tanggal_selesai ?? null;
        $periode = ($awal && $akhir)
            ? Carbon::parse($awal)->format('d/m/Y').' - '.Carbon::parse($akhir)->format('d/m/Y')
            : '-';

        $volume = (int)($po->volume_poc ?? 0);
        $harga  = (float)($po->harga_poc ?? 0);

        // shipped = sum(realisasi_kirim)
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

        // mapping row2 tabel po_customer_plan ke struktur yang dipakai UI
        $items = PoCustomerPlan::where('id_poc', $po->id_poc)
            ->orderBy('created_time')
            ->get()
            ->map(function ($r) {
                $statusMap = [0=>'Terdaftar',1=>'Masuk PR',2=>'Reschedule',3=>'Pending'];
                return [
                    'id'           => $r->id_plan,
                    'issued_at'    => $r->created_time,
                    'ship_date'    => $r->tanggal_kirim,
                    // tabel tidak punya kolom alamat; sementara pakai status_jadwal utk menampung alamat/catatan
                    'address'      => $r->status_jadwal,
                    'volume_liter' => (int)$r->volume_kirim,
                    'real_liter'   => (int)$r->realisasi_kirim,
                    'status'       => $statusMap[$r->status_plan] ?? '-',
                    'notes'        => $r->catatan_reschedule,
                ];
            });

        return response()->json(['header'=>$header, 'items'=>$items]);
    }

    // === CREATE (simpan ke po_customer_plan) ===
    public function createPoPlan(Request $request, $idPoc)
    {
        $request->validate([
            'ship_date'    => 'required|date',
            'address'      => 'nullable|string',    // UI kirim alamat; tabel tidak punya kolom alamat, kita masukkan ke status_jadwal
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
            // karena tidak ada kolom alamat, gabungkan alamat + catatan ringkas di status_jadwal
            'status_jadwal'    => trim(($request->address ? "Alamat: ".$request->address."\n" : '') . ($request->notes ?? '')),
            'catatan_reschedule' => null,
            'ask_approval'     => 0,
            'is_approved'      => 1,
            'created_time'     => now(),
            'created_ip'       => $request->ip(),
            'created_by'       => auth()->user()->name ?? 'system',
        ]);

        // hitung ulang ringkasan
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

    // === DELETE item plan ===
    public function deletePoPlan($idPoc, $id)
    {
        PoCustomerPlan::where('id_poc', $idPoc)->where('id_plan', $id)->delete();

        // patch ringkasan setelah delete
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

