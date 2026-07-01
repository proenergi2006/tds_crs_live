<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = DB::table('permissions')
            ->select('id', 'name', 'module', 'description')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        $grouped = $permissions
            ->groupBy('module')
            ->map(fn ($items, $module) => [
                'module'      => $module,
                'permissions' => $items->values(),
            ])
            ->values();

        return response()->json(['data' => $grouped]);
    }
}
