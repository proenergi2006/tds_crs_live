<?php

namespace App\Http\Controllers;

use App\Models\VendorPo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoVerificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');
        $roleId  = $request->user()->id_role;

        $q = VendorPo::with(['vendor', 'terminal', 'produks.produk']);

        if ($roleId === 2) {
            $q->where('disposisi_po', '>=', 2); // CEO
        } elseif ($roleId === 3) {
            $q->where('disposisi_po', '>=', 1); // CFO
        }

        if ($search) {
            $q->where(function($sub) use ($search) {
                $sub->where('nomor_po', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        return response()->json($q->paginate($perPage));
    }

    public function verify(Request $request, $id)
    {
        $po     = VendorPo::findOrFail($id);
        $roleId = $request->user()->id_role;

        $validator = Validator::make($request->all(), [
            'action'  => 'required|in:approve,reject',
            'summary' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data   = $validator->validated();
        $result = $data['action'] === 'approve' ? 1 : 2;

        if ($roleId === 3) {
            // Verifikasi CFO
            $po->cfo_result     = $result;
            $po->cfo_summary    = $data['summary'] ?? null;
            $po->cfo_tgl        = now();
            $po->disposisi_po   = $result === 1 ? 2 : 0;

        } elseif ($roleId === 2) {
            // Verifikasi CEO
            if ($po->disposisi_po !== 2) {
                return response()->json(['message' => 'PO harus disetujui CFO terlebih dahulu'], 403);
            }
            $po->ceo_result     = $result;
            $po->ceo_summary    = $data['summary'] ?? null;
            $po->ceo_tgl        = now();
            $po->disposisi_po   = $result === 1 ? 4 : 0;

        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $po->lastupdate_time = now();
        $po->lastupdate_by   = $request->user()->name;
        $po->save();

        return response()->json($po);
    }
}
