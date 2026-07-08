<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\UpdatePermissionRequest;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = DB::table('permissions')
            ->select('id', 'name', 'guard_name', 'module', 'description')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        $grouped = $permissions
            ->groupBy('module')
            ->map(fn($items, $module) => [
                'module'      => $module,
                'permissions' => $items->values(),
            ])
            ->values();

        return response()->json(['data' => $grouped]);
    }

    public function update(UpdatePermissionRequest $request, $id)
    {
        $permission = DB::table('permissions')->where('id', $id)->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission tidak ditemukan'], 404);
        }

        $validated = $request->validated();

        DB::table('permissions')->where('id', $id)->update($validated);

        $updated = DB::table('permissions')
            ->select('id', 'name', 'guard_name', 'module', 'description')
            ->where('id', $id)
            ->first();

        return response()->json(['data' => $updated]);
    }
}
