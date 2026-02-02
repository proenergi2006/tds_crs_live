<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users, with optional search.
     */
    public function index(Request $request)
    {
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = User::with('role','cabang');  

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'id_role'   => ['nullable', 'exists:roles,id_role'],
            'id_cabang'  => ['nullable', 'exists:cabangs,id_cabang'], // ✅
        ]);

        // hash password
        $data['password'] = Hash::make($data['password']);

        // audit fields
        $data['created_by'] = $request->user()->name;
        $data['created_at'] = now();

        $user = User::create($data);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'password'  => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'id_role'   => ['nullable', 'exists:roles,id_role'],
            'id_cabang'  => ['nullable', 'exists:cabangs,id_cabang'], // ✅
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // audit
        $data['lastupdate_by'] = $request->user()->name;
        $data['updated_at']    = now();

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
