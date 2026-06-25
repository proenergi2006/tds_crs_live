<?php

namespace App\Http\Controllers;

use App\Models\ReceiveItem;
use App\Models\ReceiveItemProduk;
use App\Models\VendorPo;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReceiveItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ReceiveItem::with([
            'vendorPo.vendor',
            'vendorPo.terminal',
            'vendorPo.produks',
            'details',
        ]);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('vendorPo', fn($q2) => $q2->where('nomor_po', 'ilike', "%{$search}%"))
                    ->orWhere('id', is_numeric($search) ? $search : 0);
            });
        }

        if ($from = $request->tanggal_dari) {
            $query->whereDate('received_at', '>=', $from);
        }
        if ($to = $request->tanggal_sampai) {
            $query->whereDate('received_at', '<=', $to);
        }
        if ($terminal = $request->id_terminal) {
            $query->whereHas('vendorPo', fn($q) => $q->where('id_terminal', $terminal));
        }
        if ($vendor = $request->id_vendor) {
            $query->whereHas('vendorPo', fn($q) => $q->where('id_vendor', $vendor));
        }

        if ($status = $request->status) {
            $query->whereHas('vendorPo', function ($q) use ($status) {
                $q->whereRaw(
                    $status === 'selesai'
                        ? 'vendor_pos.id_po IN (
                            SELECT ri.po_id FROM receive_items ri
                            JOIN receive_item_produks rip ON rip.receive_item_id = ri.id
                            JOIN vendor_pos_produks vpp ON vpp.id_po_produk = rip.po_produk_id
                            GROUP BY ri.po_id
                            HAVING SUM(rip.volume_terima) >= SUM(vpp.volume_po)
                        )'
                        : 'vendor_pos.id_po IN (
                            SELECT ri.po_id FROM receive_items ri
                            JOIN receive_item_produks rip ON rip.receive_item_id = ri.id
                            JOIN vendor_pos_produks vpp ON vpp.id_po_produk = rip.po_produk_id
                            GROUP BY ri.po_id
                            HAVING SUM(rip.volume_terima) < SUM(vpp.volume_po)
                        )'
                );
            });
        }

        $perPage = min((int) $request->per_page ?: 10, 100);
        $result  = $query->orderByDesc('received_at')->orderByDesc('id')->paginate($perPage);

        $result->getCollection()->transform(function ($item) {
            $po      = $item->vendorPo;
            $qtyDok  = $po ? $po->produks->sum('volume_po') : 0;
            $qtyAktual = $item->details->sum('volume_terima');
            $selisih   = $qtyAktual - $qtyDok;

            $totalTerimaAllReceive = ReceiveItemProduk::whereHas(
                'receiveItem',
                fn($q) => $q->where('po_id', $item->po_id)
            )->sum('volume_terima');

            $status = $totalTerimaAllReceive >= $qtyDok ? 'selesai' : 'draft';

            return [
                'id'         => $item->id,
                'no_gr'      => $item->no_gr,
                'no_po'      => $po?->nomor_po ?? '-',
                'id_po'      => $item->po_id,
                'vendor'     => $po?->vendor?->nama_vendor ?? '-',
                'qty_dok'    => $qtyDok,
                'qty_aktual' => $qtyAktual,
                'selisih'    => $selisih,
                'terminal'   => $po?->terminal?->nama_terminal ?? '-',
                'tgl_terima' => $item->received_at,
                'nama_pic'   => $item->nama_pic,
                'file_url'   => $item->file_path
                    ? Storage::disk('public')->url($item->file_path)
                    : null,
                'status'     => $status,
            ];
        });

        return response()->json($result);
    }

    public function pendingGr()
    {
        $pos = VendorPo::with([
            'vendor',
            'produks',
            'receives.details',
        ])
            ->where('disposisi_po', \App\Enums\VendorPoApprovalState::Approved->value)
            ->get()
            ->filter(fn($po) => $po->getStatusRealisasi() !== 'selesai')
            ->map(fn($po) => [
                'id_po'         => $po->id_po,
                'nomor_po'      => $po->nomor_po,
                'vendor'        => $po->vendor?->nama_vendor ?? '-',
                'volume_po'     => $po->getTotalVolumePo(),
                'volume_terima' => $po->getTotalVolumeTerima(),
                'persen'        => $po->getPersenRealisasi(),
                'status'        => $po->getStatusRealisasi(),
            ])
            ->values();

        return response()->json($pos);
    }

    public function indexByPo(int $poId)
    {
        VendorPo::findOrFail($poId);

        $receives = ReceiveItem::with('details.produk')
            ->where('po_id', $poId)
            ->orderBy('received_at', 'desc')
            ->get()
            ->map(function ($receive) {
                $receive->file_url = $receive->file_path
                    ? Storage::disk('public')->url($receive->file_path)
                    : null;
                $receive->total_volume_terima = $receive->details->sum('volume_terima');
                return $receive;
            });

        return response()->json($receives);
    }

    public function store(Request $request, $poId)
    {
        $po = VendorPo::findOrFail($poId);

        $validator = Validator::make($request->all(), [
            'received_at'             => 'required|date',
            'nama_pic'                => 'required|string|max:255',
            'file'                    => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'details'                 => 'required|array',
            'details.*.volume_terima' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        return DB::transaction(function () use ($po, $poId, $data, $request) {
            $alreadyReceived = ReceiveItemProduk::whereHas('receiveItem', fn($q) => $q->where('po_id', $poId))
                ->selectRaw('po_produk_id, SUM(volume_terima) as total_terima')
                ->groupBy('po_produk_id')
                ->pluck('total_terima', 'po_produk_id');

            $receive = new ReceiveItem();
            $receive->po_id       = $poId;
            $receive->received_at = $data['received_at'];
            $receive->nama_pic    = $data['nama_pic'];

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('receive_files', 'public');
                $receive->file_path = $path;
            }
            $receive->save();

            foreach ($data['details'] as $poProdukId => $detail) {
                $poProduk = $po->produks()->where('id_po_produk', $poProdukId)->first();
                if (! $poProduk) {
                    continue;
                }

                $volumeTerima    = (float) $detail['volume_terima'];
                $totalSebelumnya = (float) ($alreadyReceived[$poProdukId] ?? 0);
                $selisih         = $poProduk->volume_po - $totalSebelumnya - $volumeTerima;

                $item = new ReceiveItemProduk();
                $item->receive_item_id = $receive->id;
                $item->po_produk_id    = (int) $poProdukId;
                $item->id_produk       = $poProduk->id_produk;
                $item->harga_tebus     = $poProduk->harga_tebus;
                $item->volume_bl       = $volumeTerima;
                $item->volume_terima   = $volumeTerima;
                $item->selisih         = $selisih;
                $item->save();

                Stock::create([
                    'po_produk_id'    => $item->po_produk_id,
                    'receive_item_id' => $receive->id,
                    'produk_id'       => $item->id_produk,
                    'volume'          => $volumeTerima,
                    'harga_tebus'     => $item->harga_tebus,
                ]);
            }

            $receive->load('details.produk');
            return response()->json($receive, 201);
        });
    }

    public function destroy(Request $request, int $id)
    {
        $allowedRoles = [1, 5];
        if (! in_array($request->user()->id_role, $allowedRoles)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $receive = ReceiveItem::with('details')->findOrFail($id);

        if ($receive->trashed()) {
            return response()->json(['message' => 'GR sudah dibatalkan sebelumnya.'], 422);
        }

        DB::transaction(function () use ($receive) {
            Stock::where('receive_item_id', $receive->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);

            $receive->delete();
        });

        return response()->json(['message' => 'GR berhasil dibatalkan.']);
    }
}
