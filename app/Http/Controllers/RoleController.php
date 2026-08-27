<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = Role::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('as_list')) {
            return response()->json($query->get());
        }

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', 'unique:roles,name'],
            'role_desc' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $role = Role::create($data);

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        return response()->json($role);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'role_desc' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $role->update($data);

        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        // Soft-delete: tandai non-aktif
        $role->update([
            'is_active' => false,
        ]);

        return response()->json(null, 204);
    }

    // balikin cuma id permission yang udah nempel ke role ini, bukan objek lengkap
    public function permissions(Role $role)
    {
        return response()->json(['data' => $role->permissions->pluck('id')]);
    }

    // replace strategy -- permission lama diganti total sama yang dikirim, bukan di-append
    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids'   => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->syncPermissions($validated['permission_ids']);

        return response()->json(['message' => 'Permissions updated']);
    }
}
