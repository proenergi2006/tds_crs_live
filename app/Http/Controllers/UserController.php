<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = User::with('roles', 'primaryRole', 'cabang');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        if ($request->boolean('as_list')) {
            return response()->json($query->get());
        }

        $perPage = $request->query('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8'],
            'is_active'        => ['boolean'],
            'role_ids'         => ['nullable', 'array'],
            'role_ids.*'       => ['integer', 'exists:roles,id'],
            'primary_role_id'  => ['nullable', 'integer', 'exists:roles,id'],
            'id_cabang'        => ['nullable', 'exists:cabangs,id_cabang'],
            'no_telepon'       => ['nullable', 'string', 'max:30'],
        ]);

        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        $primaryRoleId = $data['primary_role_id'] ?? null;
        if ($primaryRoleId !== null && ! in_array($primaryRoleId, $roleIds, true)) {
            return response()->json(['message' => 'primary_role_id harus salah satu dari role_ids yang dipilih.'], 422);
        }
        $data['primary_role_id'] = $this->resolvePrimaryRoleId($roleIds, $primaryRoleId);

        // hash password
        $data['password'] = Hash::make($data['password']);

        // audit fields
        $data['created_by'] = $request->user()->name;
        $data['created_at'] = now();

        $user = User::create($data);
        $user->syncRoles($roleIds);

        return response()->json($user, 201);
    }

    // multi-role: primary_role_id nentuin brand+tab default, default ke role pertama kalau ga dipilih eksplisit
    private function resolvePrimaryRoleId(array $roleIds, ?int $primaryRoleId): ?int
    {
        return $primaryRoleId ?? ($roleIds[0] ?? null);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'password'         => ['nullable', 'string', 'min:8'],
            'is_active'        => ['boolean'],
            'role_ids'         => ['nullable', 'array'],
            'role_ids.*'       => ['integer', 'exists:roles,id'],
            'primary_role_id'  => ['nullable', 'integer', 'exists:roles,id'],
            'id_cabang'        => ['nullable', 'exists:cabangs,id_cabang'],
            'no_telepon'       => ['nullable', 'string', 'max:30'],
        ]);

        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        $primaryRoleId = $data['primary_role_id'] ?? null;
        if ($primaryRoleId !== null && ! in_array($primaryRoleId, $roleIds, true)) {
            return response()->json(['message' => 'primary_role_id harus salah satu dari role_ids yang dipilih.'], 422);
        }
        $data['primary_role_id'] = $this->resolvePrimaryRoleId($roleIds, $primaryRoleId);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // audit
        $data['lastupdate_by'] = $request->user()->name;
        $data['updated_at']    = now();

        $user->update($data);
        $user->syncRoles($roleIds);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function resetPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }
    
        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();
    
        return response()->json([
            'message' => 'Password berhasil direset'
        ]);
    }

    
}
