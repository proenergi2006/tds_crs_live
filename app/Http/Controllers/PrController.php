<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'items' => 'required|array|min:1',
            'items.*.id_plan' => 'required|integer|exists:po_customer_plan,id_plan',
            'items.*.volume'  => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {
            // Ambil 1 plan buat referensi cabang
            $firstPlan = DB::table('po_customer_plan as p')
                ->leftJoin('po_customers as po','po.id_poc','=','p.id_poc')
                ->leftJoin('customers as c','c.id_customer','=','po.id_customer')
                ->leftJoin('cabangs as cb','cb.id_cabang','=','c.id_cabang')
                ->selectRaw("
                    p.id_poc,
                    po.id_penawaran,
                    c.id_cabang,
                    COALESCE(cb.inisial_cabang, 'XXX') as inisial_cabang
                ")
                ->where('p.id_plan', $data['items'][0]['id_plan'])
                ->first();

            $inisialCabang = $firstPlan->inisial_cabang ?? 'XXX';
            $tanggalPr     = now()->toDateString();
            $nomorPr       = $this->generateNomorPr($inisialCabang);

            // =======================
            // INSERT HEADER PR (FIX)
            // =======================
            // Wajib sebut kolom PK 'id_pr' sebagai argumen ke-2
            $idPr = DB::table('pro_pr')->insertGetId([
                'id_wilayah'        => null,
                'id_group'          => null,
                'nomor_pr'          => $nomorPr,
                'tanggal_pr'        => $tanggalPr,
                'logistik_result'   => 0,
                'finance_result'    => 0,
                'sm_result'         => 0,
                'purchasing_result' => 0,
                'cfo_result'        => 0,
                'ceo_result'        => 0,
                'is_ceo'            => 0,
                'revert_cfo'        => 0,
                'revert_ceo'        => 0,
                'disposisi_pr'      => 0,
                'ada_ar'            => 0,
                'ar_approved'       => 0,
                'is_edited'         => 0,
                'coo_result'        => 0,
                'jam_submit'        => now(),
                'submit_bm'         => now(),
            ], 'id_pr'); // <-- ini yang bikin Postgres happy

            // Insert detail per PLAN
            foreach ($data['items'] as $it) {
                $plan = DB::table('po_customer_plan as p')
                    ->leftJoin('po_customers as po','po.id_poc','=','p.id_poc')
                    ->leftJoin('customers as cu','cu.id_customer','=','po.id_customer')
                    ->selectRaw('
                        p.id_plan, p.id_poc, p.volume_kirim, p.realisasi_kirim,
                        po.id_penawaran, cu.nama_perusahaan
                    ')
                    ->where('p.id_plan', $it['id_plan'])
                    ->first();

                // (Opsional) ambil label produk gabungan kalau mau tampil di PR detail
                $produkLabel = DB::table('penawaran_items as pi')
                    ->leftJoin('produks as pr','pr.id_produk','=','pi.id_produk')
                    ->where('pi.id_penawaran', $plan->id_penawaran)
                    ->selectRaw("COALESCE(string_agg(COALESCE(pr.nama_produk, pi.id_produk::text), ', '), '-') as nama")
                    ->value('nama') ?? '-';

                DB::table('pro_pr_detail')->insert([
                    'id_pr'             => $idPr,
                    'id_plan'           => $plan->id_plan,
                    'produk'            => $produkLabel, // boleh null juga
                    'volume'            => (int)$it['volume'],
                    'vol_potongan'      => 0,
                    'vol_ket'           => null,
                    'vol_ori'           => (int)$it['volume'],
                    'transport'         => null,
                    'pr_mobil'          => 1,
                    'pr_top'            => null,
                    'pr_actual_top'     => null,
                    'pr_pelanggan'      => substr((string)$plan->nama_perusahaan, 0, 30),
                    'pr_ar_notyet'      => null,
                    'pr_ar_satu'        => null,
                    'pr_ar_dua'         => null,
                    'pr_kredit_limit'   => null,
                    'pr_terminal'       => null,
                    'pr_vendor'         => null,
                    'pr_harga_beli'     => null, // akan terisi saat alokasi stok / verifikasi
                    'pr_price_list'     => 0,
                    'nomor_lo_pr'       => null,
                    'schedule_payment'  => null,
                    'is_approved'       => 0,
                    'splitted_from_pr'  => null,
                    'vol_ori_pr'        => null,
                    'splitted_from'     => null,
                    'nomor_do_accurate' => null,
                    'no_do_acurate'     => null,
                    'pr_po'             => null,
                    'no_do_syop'        => null,
                    'cabang'            => null,
                    'nomor_po_supplier' => null,
                    'id_po_supplier'    => null,
                    'id_po_receive'     => null,
                    'is_split'          => 0,
                ]);
            }

            // Update status plan → 1 (Masuk PR) agar hilang dari Delivery Plan list
            $planIds = collect($data['items'])->pluck('id_plan')->all();
            DB::table('po_customer_plan')->whereIn('id_plan', $planIds)->update([
                'status_plan' => 1,
            ]);

            return response()->json([
                'success'    => true,
                'id_pr'      => $idPr,
                'nomor_pr'   => $nomorPr,
                'tanggal_pr' => $tanggalPr,
            ]);
        });
    }

    /** Format nomor: 0001/TDS/DR/{INISIAL}/{ROMAN}/{YYYY} */
    private function generateNomorPr(string $inisialCabang): string
    {
        $now   = now();
        $yyyy  = (int)$now->format('Y');
        $roman = $this->toRoman((int)$now->format('n'));

        // Cari urutan terbesar di bulan & tahun berjalan.
        // Pakai regex Postgres untuk ambil digit diawal (lebih aman).
        $maxSeq = DB::table('pro_pr')
            ->whereBetween('tanggal_pr', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw("MAX( (substring(nomor_pr from '^[0-9]+'))::int ) as maxseq")
            ->value('maxseq');

        $next = (int)$maxSeq + 1;
        $seq4 = str_pad((string)$next, 4, '0', STR_PAD_LEFT);

        return "{$seq4}/TDS/DR/{$inisialCabang}/{$roman}/{$yyyy}";
    }

    private function toRoman(int $month): string
    {
        $map = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'];
        return $map[$month] ?? 'I';
    }
}
