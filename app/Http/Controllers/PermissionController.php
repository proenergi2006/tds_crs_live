<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = DB::table('permissions')
            ->leftJoin('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.guard_name',
                'permissions.module',
                'permissions.description',
                DB::raw('COUNT(role_has_permissions.role_id) as roles_count')
            )
            ->groupBy(
                'permissions.id',
                'permissions.name',
                'permissions.guard_name',
                'permissions.module',
                'permissions.description'
            )
            ->orderBy('permissions.module')
            ->orderBy('permissions.name')
            ->get();

        $grouped = $permissions
            ->groupBy('module')
            ->map(fn($items, $module) => [
                'module'      => $module,
                'permissions' => $items->map(fn($item) => [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'guard_name'  => $item->guard_name,
                    'module'      => $item->module,
                    'description' => $item->description,
                    'roles_count' => (int) $item->roles_count,
                ])->values(),
            ])
            ->values();

        return response()->json(['data' => $grouped]);
    }

    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();

        $id = DB::table('permissions')->insertGetId([
            'name'        => $validated['name'],
            'guard_name'  => 'web',
            'module'      => $validated['module'],
            'description' => $validated['description'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = DB::table('permissions')
            ->select('id', 'name', 'guard_name', 'module', 'description')
            ->where('id', $id)
            ->first();

        return response()->json([
            'data' => [
                'id'          => $created->id,
                'name'        => $created->name,
                'guard_name'  => $created->guard_name,
                'module'      => $created->module,
                'description' => $created->description,
                'roles_count' => 0,
            ],
        ], 201);
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

    public function destroy($id)
    {
        $permission = DB::table('permissions')->where('id', $id)->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission tidak ditemukan'], 404);
        }

        DB::table('permissions')->where('id', $id)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(null, 204);
    }
}
