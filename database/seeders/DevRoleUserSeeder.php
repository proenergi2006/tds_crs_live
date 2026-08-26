<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Dev-only, manual run: php artisan db:seed --class=DevRoleUserSeeder -- bikin 1 user per role (role@mail.dev, pass sama) buat testing RBAC.
class DevRoleUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::all() as $role) {
            $email = Str::slug($role->name).'@mail.dev';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $role->name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'primary_role_id' => $role->id,
                ]
            );

            $user->syncRoles([$role->id]);
        }
    }
}
