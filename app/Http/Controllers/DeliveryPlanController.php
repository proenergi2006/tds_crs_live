<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class DeliveryPlanController extends Controller
{
    private array $statusMap = [0=>'Terdaftar',1=>'Masuk PR',2=>'Reschedule',3=>'Pending'];

    /**
     * GET /api/logistics/delivery-plans
     * Query:
     * - q, from, to, status (default 0), overdue, urgent, page, per_page
     */
    public function index(Request $req)
    {
        $q       = trim((string)$req->query('q', ''));
        $from    = $this->toYmd($req->query('from'));
        $to      = $this->toYmd($req->query('to'));
        $status  = $req->query('status', '0'); // default: hanya yg belum PR
        $overdue = (int)$req->query('overdue', 0);
        $urgent  = $req->query('urgent', null);
        $perPage = min(max((int)$req->query('per_page', 25), 1), 200);

        $base = DB::table('po_customer_plan as p')
            ->leftJoin('po_customers as po', 'po.id_poc', '=', 'p.id_poc')
            ->leftJoin('customers as c', 'c.id_customer', '=', 'po.id_customer')
            ->selectRaw("
                p.id_plan, p.id_poc, p.tanggal_kirim, p.created_time,
                p.volume_kirim, p.vol_ori_plan, p.realisasi_kirim,
                p.status_plan, p.status_jadwal, p.catatan_reschedule,
                p.is_urgent, p.splitted_from_plan,
                p.top_plan, p.actual_top_plan, p.pelanggan_plan,
                p.ar_notyet, p.ar_satu, p.ar_dua, p.kredit_limit,
                po.nomor_poc, po.tanggal_poc,
                po.id_penawaran,
                c.nama_perusahaan,
                CONCAT('PO-', LPAD(p.id_poc::text, 5, '0')) as doc_code
            ");

        if ($q !== '') {
            $base->where(function($w) use ($q) {
                $w->where('c.nama_perusahaan','ILIKE',"%{$q}%")
                  ->orWhere('po.nomor_poc',$q)
                  ->orWhereRaw("CONCAT('PO-', LPAD(p.id_poc::text, 5, '0')) = ?", [$q]);
            });
        }
        if ($from) $base->whereDate('p.tanggal_kirim','>=',$from);
        if ($to)   $base->whereDate('p.tanggal_kirim','<=',$to);

        // default hanya status 0
        if ($status !== null && $status !== '') {
            $base->where('p.status_plan', (int)$status);
        }

        if (!is_null($urgent) && $urgent !== '') {
            $base->where('p.is_urgent', (int)$urgent ? 1 : 0);
        }
        if ($overdue === 1) {
            $today = Carbon::today()->toDateString();
            $base->whereDate('p.tanggal_kirim','<',$today)
                 ->whereRaw('COALESCE(p.realisasi_kirim,0) = 0');
        }

        $base->orderBy('p.tanggal_kirim')->orderByDesc('p.created_time');

        $data = $base->paginate($perPage);

        // ==== ambil produk per id_penawaran muncul di halaman ====
        $penawaranIds = collect($data->items())->pluck('id_penawaran')->filter()->unique()->values();

        $produkByPenawaran = collect();
        if ($penawaranIds->isNotEmpty()) {
            $rowsProduk = DB::table('penawaran_items as pi')
                ->leftJoin('produks as pr','pr.id_produk','=','pi.id_produk')
                ->whereIn('pi.id_penawaran', $penawaranIds)
                ->selectRaw('
                    pi.id_penawaran,
                    pi.id_penawaran_item,
                    pi.id_produk,
                    COALESCE(pr.nama_produk, pi.id_produk::text) as nama_produk
                ')
                ->orderBy('pi.id_penawaran_item')
                ->get();

            $produkByPenawaran = $rowsProduk->groupBy('id_penawaran')->map(function($grp){
                return $grp->map(fn($r)=>[
                    'id_penawaran_item' => (int)$r->id_penawaran_item,
                    'id_produk'         => (int)$r->id_produk,
                    'nama_produk'       => $r->nama_produk,
                ])->values();
            });
        }

        $items = collect($data->items())->map(function ($r) use ($produkByPenawaran) {
            $row = $this->mapRow($r);
            $list = $produkByPenawaran->get($r->id_penawaran, collect());
            $row['products'] = $list;
            if ($list->count()) {
                $names = $list->pluck('nama_produk')->all();
                $row['produk'] = count($names) <= 2
                    ? implode(', ', $names)
                    : ($names[0].', '.$names[1].' +'.(count($names)-2).' lainnya');
            } else {
                $row['produk'] = null;
            }
            return $row;
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/logistics/delivery-plans/{id}
     */
    public function show($id)
    {
        $r = DB::table('po_customer_plan as p')
            ->leftJoin('po_customers as po','po.id_poc','=','p.id_poc')
            ->leftJoin('customers as c','c.id_customer','=','po.id_customer')
            ->where('p.id_plan',$id)
            ->selectRaw("
                p.id_plan, p.id_poc, p.tanggal_kirim, p.created_time,
                p.volume_kirim, p.vol_ori_plan, p.realisasi_kirim,
                p.status_plan, p.status_jadwal, p.catatan_reschedule,
                p.is_urgent, p.splitted_from_plan,
                p.top_plan, p.actual_top_plan, p.pelanggan_plan,
                p.ar_notyet, p.ar_satu, p.ar_dua, p.kredit_limit,
                po.nomor_poc, po.tanggal_poc,
                po.id_penawaran,
                c.nama_perusahaan,
                CONCAT('PO-', LPAD(p.id_poc::text, 5, '0')) as doc_code
            ")->first();

        abort_if(!$r, 404);

        $row = $this->mapRow($r);

        $products = collect();
        if (!empty($r->id_penawaran)) {
            $products = DB::table('penawaran_items as pi')
                ->leftJoin('produks as pr','pr.id_produk','=','pi.id_produk')
                ->where('pi.id_penawaran', $r->id_penawaran)
                ->selectRaw('pi.id_penawaran_item, pi.id_produk, COALESCE(pr.nama_produk, pi.id_produk::text) as nama_produk')
                ->orderBy('pi.id_penawaran_item')
                ->get()
                ->map(fn($x)=>[
                    'id_penawaran_item'=>(int)$x->id_penawaran_item,
                    'id_produk'        =>(int)$x->id_produk,
                    'nama_produk'      =>$x->nama_produk,
                ]);
        }

        $row['products'] = $products;
        if ($products->count()) {
            $names = $products->pluck('nama_produk')->all();
            $row['produk'] = count($names) <= 2
                ? implode(', ', $names)
                : ($names[0].', '.$names[1].' +'.(count($names)-2).' lainnya');
        } else {
            $row['produk'] = null;
        }

        return response()->json($row);
    }

    public function update(Request $req, $id)
    {
        $data = $req->validate([
            'realisasi_kirim'    => 'nullable|integer|min:0',
            'status_plan'        => 'nullable|integer|in:0,1,2,3',
            'is_urgent'          => 'nullable|boolean',
            'status_jadwal'      => 'nullable|string',
            'catatan_reschedule' => 'nullable|string',
        ]);

        $payload = [];
        foreach (['realisasi_kirim','status_plan','status_jadwal','catatan_reschedule'] as $k) {
            if ($req->filled($k)) $payload[$k] = $data[$k];
        }
        if ($req->has('is_urgent')) {
            $payload['is_urgent'] = $req->boolean('is_urgent') ? 1 : 0;
        }

        if (!empty($payload)) {
            DB::table('po_customer_plan')->where('id_plan',$id)->update($payload);
        }

        return response()->json(['success'=>true]);
    }

    public function updateVolume(Request $req, $id)
    {
        $data = $req->validate(['volume_kirim' => 'required|integer|min:1']);

        $row = DB::table('po_customer_plan')->where('id_plan',$id)->first();
        abort_if(!$row, 404);

        $real = (int)($row->realisasi_kirim ?? 0);
        if ($data['volume_kirim'] < $real) {
            throw ValidationException::withMessages([
                'volume_kirim' => 'Volume tidak boleh kurang dari realisasi ('.$real.').'
            ]);
        }

        DB::table('po_customer_plan')->where('id_plan',$id)->update([
            'volume_kirim' => (int)$data['volume_kirim']
        ]);

        return response()->json(['success'=>true]);
    }

    public function split(Request $req, $id)
    {
        $data = $req->validate(['volume' => 'required|integer|min:1']);

        $src = DB::table('po_customer_plan')->where('id_plan',$id)->first();
        abort_if(!$src, 404);

        $sisa = (int)$src->volume_kirim - (int)$src->realisasi_kirim;
        if ($data['volume'] >= $sisa) {
            throw ValidationException::withMessages([
                'volume' => 'Volume split harus lebih kecil dari sisa ('.$sisa.').'
            ]);
        }

        return DB::transaction(function() use ($src,$data,$req) {
            DB::table('po_customer_plan')->where('id_plan',$src->id_plan)->update([
                'volume_kirim' => (int)$src->volume_kirim - (int)$data['volume'],
            ]);

            $newId = DB::table('po_customer_plan')->insertGetId([
                'id_poc'             => $src->id_poc,
                'id_lcr'             => $src->id_lcr,
                'tanggal_kirim'      => $src->tanggal_kirim,
                'volume_kirim'       => (int)$data['volume'],
                'vol_ori_plan'       => (int)$data['volume'],
                'realisasi_kirim'    => 0,
                'is_urgent'          => $src->is_urgent,
                'status_plan'        => $src->status_plan,
                'status_jadwal'      => $src->status_jadwal,
                'catatan_reschedule' => $src->catatan_reschedule,
                'top_plan'           => $src->top_plan,
                'actual_top_plan'    => $src->actual_top_plan,
                'pelanggan_plan'     => $src->pelanggan_plan,
                'ar_notyet'          => $src->ar_notyet,
                'ar_satu'            => $src->ar_satu,
                'ar_dua'             => $src->ar_dua,
                'kredit_limit'       => $src->kredit_limit,
                'splitted_from_plan' => $src->id_plan,
                'ask_approval'       => 0,
                'is_approved'        => 1,
                'created_time'       => now(),
                'created_ip'         => $req->ip(),
                'created_by'         => auth()->user()->name ?? 'system',
            ]);

            return response()->json(['success'=>true,'new_id'=>$newId]);
        });
    }

    // ================= Helpers =================

    private function mapRow($r): array
    {
        $address = $this->extractAddress($r->status_jadwal);
        $notes   = $this->extractNotes($r->status_jadwal, $r->catatan_reschedule);

        return [
            'id'               => (int)$r->id_plan,
            'id_poc'           => (int)$r->id_poc,
            'doc_code'         => $r->doc_code,
            'nomor_poc'        => $r->nomor_poc,
            'tanggal_poc'      => $r->tanggal_poc,
            'customer'         => $r->nama_perusahaan,
            'issued_at'        => $r->created_time,
            'ship_date'        => $r->tanggal_kirim,
            'address'          => $address ?: ($r->status_jadwal ?? '-'),
            'notes'            => $notes,
            'status_code'      => (int)$r->status_plan,
            'status_label'     => $this->statusMap[$r->status_plan] ?? '-',
            'is_urgent'        => (int)$r->is_urgent,
            'volume_liter'     => (int)$r->volume_kirim,
            'vol_ori_plan'     => (int)($r->vol_ori_plan ?? $r->volume_kirim),
            'real_liter'       => (int)($r->realisasi_kirim ?? 0),
            'splitted_from'    => $r->splitted_from_plan,
        ];
    }

    private function extractAddress(?string $blob): ?string
    {
        if (!$blob) return null;
        if (preg_match('/Alamat:\s*(.+?)(?:\r?\n|$)/i', $blob, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractNotes(?string $blob, ?string $reschedNote = null): ?string
    {
        $notesFromBlob = null;
        if ($blob && preg_match('/Alamat:\s*.+?(?:\r?\n|$)(.*)/is', $blob, $m)) {
            $notesFromBlob = trim($m[1]);
        }
        $parts = array_filter([$notesFromBlob, $reschedNote], fn($x)=>!empty($x));
        if (!$parts) return null;
        return trim(implode("\n---\n", $parts));
    }

    private function toYmd($s): ?string
    {
        if (!$s) return null;
        $s = trim($s);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) return $s;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/',$s,$m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        return null;
    }
}
