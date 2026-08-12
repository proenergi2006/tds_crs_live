<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalPendingCountController extends Controller
{
    // role tanpa stage di provider manapun tetap 200 + breakdown kosong, bukan 403 -- biar endpoint ini generik
    public function __invoke(Request $request): JsonResponse
    {
        $roleId = $request->user()->id_role;

        $breakdown = [];
        $total     = 0;

        foreach (config('approvals.providers') as $providerClass) {
            $provider = app($providerClass);

            if (! in_array($roleId, $provider->stageRoleIds(), true)) {
                continue;
            }

            $count = $provider->pendingCountFor($roleId);

            $breakdown[$provider->label()] = $count;
            $total += $count;
        }

        return response()->json([
            'total'     => $total,
            'breakdown' => $breakdown,
        ]);
    }
}
