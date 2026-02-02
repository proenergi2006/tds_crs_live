<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\StockAllocation;

class DeliveryRequestController extends Controller
{
    /**
     * GET /api/procurement/delivery-requests
     * Query: from,to (YYYY-MM-DD atau DD/MM/YYYY), page, per_page
     */
    public function index(Request $req)
    {
        [$from, $to] = $this->parseRange($req);

        $q = DB::table('pro_pr as pr')
            ->leftJoin('pro_pr_detail as d', 'd.id_pr', '=', 'pr.id_pr')
            ->leftJoin('po_customer_plan as pcp', 'pcp.id_plan', '=', 'd.id_plan')
            ->leftJoin('po_customers as poc', 'poc.id_poc', '=', 'pcp.id_poc')
            ->leftJoin('customers as c', 'c.id_customer', '=', 'poc.id_customer')
            ->selectRaw("
                pr.id_pr,
                pr.nomor_pr,
                pr.tanggal_pr,
                pr.disposisi_pr,
                COALESCE(SUM(d.volume), 0)       AS total_volume,
                COALESCE(SUM(d.vol_ori), 0)      AS total_volume_awal,
                COALESCE(SUM(d.vol_potongan), 0) AS total_potongan,
                string_agg(DISTINCT c.nama_perusahaan, E'\n') AS agg_customers,
                string_agg(DISTINCT poc.nomor_poc,     E'\n') AS agg_nomor_poc
            ")
            ->when($from, fn($w) => $w->whereDate('pr.tanggal_pr','>=',$from))
            ->when($to,   fn($w) => $w->whereDate('pr.tanggal_pr','<=',$to))
            ->groupBy('pr.id_pr','pr.nomor_pr','pr.tanggal_pr','pr.disposisi_pr')
            ->orderByDesc('pr.tanggal_pr')
            ->orderByDesc('pr.id_pr');

        $perPage = min(max((int)$req->query('per_page', 25), 1), 200);
        $data = $q->paginate($perPage);

        $items = collect($data->items())->map(function($r){
            $sisa = ((int)$r->total_volume) - ((int)$r->total_potongan);
            $disp = (int)($r->disposisi_pr ?? 0);
            $dispLabel = match ($disp) {
                1 => 'Terverifikasi Purchasing',
                2 => 'Purchase Order',
                default => 'Menunggu Verifikasi Purchasing',
            };

            return [
                'id_pr'           => (int)$r->id_pr,
                'nomor_pr'        => $r->nomor_pr,
                'tanggal_pr'      => $r->tanggal_pr,
                'total_volume'    => (int)$r->total_volume,
                'total_sisa'      => max(0, (int)$sisa),
                'customers'       => $r->agg_customers ?? '',
                'nomor_pos'       => $r->agg_nomor_poc ?? '',
                'disposisi'       => $disp,
                'disposisi_label' => $dispLabel,
            ];
        });

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/procurement/delivery-requests/{id}
     * Detail PR + items
     */
    public function show($id)
    {
        $pr = DB::table('pro_pr')->where('id_pr',$id)->first();
        abort_if(!$pr, 404);

        // Ambil detail + ringkasan alokasi utk hitung avg harga beli
        $rows = DB::table('pro_pr_detail as d')
            ->leftJoin('po_customer_plan as pcp','pcp.id_plan','=','d.id_plan')
            ->leftJoin('po_customers as poc','poc.id_poc','=','pcp.id_poc')
            ->leftJoin('customers as c','c.id_customer','=','poc.id_customer')
            ->leftJoin(DB::raw("
                (
                  SELECT sa.id_prd,
                         SUM(sa.qty * sa.harga_tebus)::numeric(22,4) AS sum_cost,
                         SUM(sa.qty)::numeric(22,4)                  AS sum_qty
                  FROM stock_allocations sa
                  GROUP BY sa.id_prd
                ) alloc
            "), 'alloc.id_prd', '=', 'd.id_prd')
            ->where('d.id_pr',$id)
            ->selectRaw("
                d.id_prd,
                d.id_plan,
                d.produk,
                d.volume,
                COALESCE(d.vol_potongan,0)    AS vol_potongan,
                COALESCE(d.vol_ori,d.volume)  AS vol_ori,
                COALESCE(alloc.sum_cost/NULLIF(alloc.sum_qty,0),0) AS avg_buy,
                c.nama_perusahaan  AS customer,
                poc.nomor_poc      AS nomor_poc,
                poc.harga_poc      AS harga_jual,
                pcp.status_jadwal  AS status_jadwal,
                pcp.catatan_reschedule AS catatan_r
            ")
            ->orderBy('d.id_prd')
            ->get();

        $items = $rows->map(function($r){
            [$alamat, $catatan] = self::splitAddressNotes($r->status_jadwal, $r->catatan_r);

            $vol        = (int)$r->volume;
            $dialokasi  = (int)$r->vol_potongan;
            $remain     = max(0, $vol - $dialokasi);
            $hargaBeli  = (float)$r->avg_buy;              // ⬅️ hasil rata-rata dari alokasi
            $hargaJual  = (float)($r->harga_jual ?? 0);
            $npPerLtr   = $hargaJual - $hargaBeli;
            $npTotal    = $npPerLtr * $vol;

            return [
                'id_prd'       => (int)$r->id_prd,
                'produk'       => $r->produk,
                'ukuran'       => null,
                'customer'     => $r->customer,
                'alamat_kirim' => $alamat ?: '-',
                'catatan'      => $catatan ?: null,
                'nomor_poc'    => $r->nomor_poc,
                'volume'       => $vol,
                'allocated'    => $dialokasi,
                'remain'       => $remain,
                'harga_beli'   => $hargaBeli,               // ⬅️ tampil di UI
                'harga_jual'   => $hargaJual,
                'net_profit_per_liter' => $npPerLtr,
                'net_profit_total'     => $npTotal,
                'produk_id'    => null,
            ];
        });

        return response()->json([
            'header' => [
                'id_pr'           => $pr->id_pr,
                'nomor_pr'        => $pr->nomor_pr,
                'tanggal_pr'      => $pr->tanggal_pr,
                'disposisi'       => (int)($pr->disposisi_pr ?? 0),
                'disposisi_label' => match((int)($pr->disposisi_pr ?? 0)){
                    1 => 'Terverifikasi Purchasing',
                    2 => 'Purchase Order',
                    default => 'Menunggu Verifikasi Purchasing',
                },
            ],
            'items' => $items,
        ]);
    }

    /**
     * POST /api/procurement/delivery-requests/verify
     * Body: { summary?: string }
     */
    public function verify(Request $req, $id)
    {
        $data = $req->validate([
            'summary' => 'nullable|string'
        ]);

        // (Opsional) pastikan tidak ada sisa volume
        // $sisa = DB::table('pro_pr_detail')->where('id_pr',$id)
        //     ->selectRaw('SUM(GREATEST(volume-COALESCE(vol_potongan,0),0)) AS sisa')->value('sisa');
        // if ($sisa > 0) {
        //     throw ValidationException::withMessages([
        //         'verify' => "Masih ada sisa volume belum dialokasikan: {$sisa} liter."
        //     ]);
        // }

        $affected = DB::table('pro_pr')->where('id_pr',$id)->update([
            'disposisi_pr'        => 1,
            'purchasing_result'   => 1,
            'purchasing_tanggal'  => now(),
            'purchasing_summary'  => $data['summary'] ?? null,
            'purchasing_pic'      => auth()->user()->name ?? 'system',
        ]);

        abort_if(!$affected, 404, 'PR tidak ditemukan');

        return response()->json(['success'=>true]);
    }

    /**
     * POST /api/procurement/delivery-requests/allocate
     * Body:
     * {
     *   items: [
     *     { id_prd: int, produk_id: int|null, allocations: [{ stock_id:int, qty:number }, ...] },
     *     ...
     *   ]
     * }
     */
    public function allocate(Request $req)
    {
        $data = $req->validate([
            'items'                               => 'required|array|min:1',
            'items.*.id_prd'                      => 'required|integer|min:1',
            'items.*.produk_id'                   => 'nullable|integer',
            'items.*.allocations'                 => 'required|array|min:1',
            'items.*.allocations.*.stock_id'      => 'required|integer|min:1',
            'items.*.allocations.*.qty'           => 'required|numeric|min:1',
        ]);

        DB::transaction(function() use ($data) {
            foreach ($data['items'] as $it) {
                $idPrd = (int)$it['id_prd'];

                $totalQty = 0;
                foreach ($it['allocations'] as $al) {
                    $stock = DB::table('stocks')->lockForUpdate()->where('id',$al['stock_id'])->first();
                    abort_unless($stock, 404, 'Stock tidak ditemukan');

                    $qty = (float)$al['qty'];
                    if ($qty > (float)$stock->volume) {
                        throw ValidationException::withMessages([
                            'qty' => "Qty alokasi melebihi stok (avail: {$stock->volume})"
                        ]);
                    }

                    // catat alokasi
                    StockAllocation::create([
                        'stock_id'         => (int)$stock->id,
                        'id_prd'           => $idPrd,
                        'qty'              => $qty,
                        'allocated_volume' => $qty,                       // penting: kolom NOT NULL
                        'harga_tebus'      => (float)$stock->harga_tebus,
                        'created_by'       => auth()->user()->name ?? 'system',
                    ]);

                    // kurangi stok
                    DB::table('stocks')->where('id',$stock->id)->update([
                        'volume' => DB::raw("volume - {$qty}")
                    ]);

                    $totalQty += $qty;
                }

                // update vol_potongan pada detail PR
                DB::table('pro_pr_detail')->where('id_prd',$idPrd)->update([
                    'vol_potongan' => DB::raw('COALESCE(vol_potongan,0) + '.$totalQty),
                ]);
            }
        });

        return response()->json(['success'=>true]);
    }

    // ================= Helpers =================

    private static function splitAddressNotes(?string $blob, ?string $reschedNote = null): array
    {
        $alamat = null; $notes = null;
        if ($blob) {
            if (preg_match('/Alamat:\s*(.+?)(?:\r?\n|$)/i', $blob, $m)) $alamat = trim($m[1]);
            if (preg_match('/Alamat:\s*.+?(?:\r?\n|$)(.*)/is', $blob, $m2)) $notes = trim($m2[1]);
        }
        $fullNotes = trim(implode("\n---\n", array_filter([$notes, $reschedNote])));
        return [$alamat, $fullNotes ?: null];
    }

    private function parseRange(Request $req): array
    {
        $from = $this->toYmd($req->query('from'));
        $to   = $this->toYmd($req->query('to'));
        return [$from, $to];
    }

    private function toYmd($s): ?string
    {
        if (!$s) return null;
        $s = trim($s);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) return $s;              // YYYY-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/',$s,$m)) return "{$m[3]}-{$m[2]}-{$m[1]}"; // DD/MM/YYYY
        return null;
    }
}
