<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalPendingCountController extends Controller
{
    // union lintas semua role user; role yang gak punya stage tetep 200 breakdown kosong, biar endpoint ini generik
    public function __invoke(Request $request): JsonResponse
    {
        $roleIds = $request->user()->roles->pluck('id')->all();

        $breakdown = [];
        $total     = 0;

        foreach (config('approvals.providers') as $providerClass) {
            $provider = app($providerClass);

            $matchingRoles = array_intersect($roleIds, $provider->stageRoleIds());

            if (empty($matchingRoles)) {
                continue;
            }

            $count = array_sum(array_map(fn ($rid) => $provider->pendingCountFor($rid), $matchingRoles));

            $breakdown[$provider->label()] = $count;
            $total += $count;
        }

        return response()->json([
            'total'     => $total,
            'breakdown' => $breakdown,
        ]);
    }
}
