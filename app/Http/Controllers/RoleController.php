<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);
    
        $query = Role::query();
    
        if ($search) {
            $query->where('role_name', 'like', "%{$search}%");
        }
    
        $paginated = $query->paginate($perPage);
    
        return response()->json($paginated);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
{
    $data = $request->validate([
        'role_name'   => ['required', 'string', 'max:100', 'unique:roles,role_name'],
        'role_desc'   => ['required', 'string'],         // jadi required
        'is_active'   => ['boolean'],
        // 'created_by' diambil dari Auth, tidak divalidate dari form
    ]);

    // set timestamps manual
    $data['created_time'] = now();

    // ambil nama user dari session/auth
    $data['created_by'] = $request->user()->name;

    $role = Role::create($data);

    return response()->json($role, 201);
}

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        return response()->json($role);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'role_name'      => ['sometimes', 'string', 'max:100', Rule::unique('roles','role_name')->ignore($role->id_role,'id_role')],
            'role_desc'      => ['nullable', 'string'],
            'is_active'      => ['boolean'],
            'lastupdate_by'  => ['nullable', 'string'],
        ]);

        $data['lastupdate_time'] = now();

        $role->update($data);

        return response()->json($role);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        // Soft-delete: tandai non‐aktif
        $role->update([
            'is_active'       => false,
            'lastupdate_time' => now(),
        ]);

        return response()->json(null, 204);
    }
}
