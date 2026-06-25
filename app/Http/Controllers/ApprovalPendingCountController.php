<?php

namespace App\Http\Controllers;

use App\Models\Penawaran;
use App\Models\VendorPo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalPendingCountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->id_role !== 2) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vendorPo  = VendorPo::where('disposisi_po', \App\Enums\VendorPoApprovalState::WaitingCeo->value)->count();
        $penawaran = Penawaran::where('disposisi_penawaran', 3)->count();

        return response()->json([
            'total'     => $vendorPo + $penawaran,
            'breakdown' => [
                'vendor_po' => $vendorPo,
                'penawaran' => $penawaran,
            ],
        ]);
    }
}
