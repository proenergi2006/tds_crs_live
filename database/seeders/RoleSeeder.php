<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// id role dipakein eksplisit di banyak tempat (permission seeder dst), makanya harus fix bukan auto-increment.
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Administrator', 'role_desc' => null],
            ['id' => 2, 'name' => 'CEO', 'role_desc' => '-'],
            ['id' => 3, 'name' => 'CFO', 'role_desc' => '-'],
            ['id' => 4, 'name' => 'Key Account', 'role_desc' => '-'],
            ['id' => 5, 'name' => 'Procurement', 'role_desc' => '-'],
            ['id' => 6, 'name' => 'Logistik', 'role_desc' => '-'],
            ['id' => 7, 'name' => 'Logistik HO', 'role_desc' => '-'],
            ['id' => 8, 'name' => 'branch manager', 'role_desc' => '-'],
            ['id' => 9, 'name' => 'admin finance', 'role_desc' => '-'],
            ['id' => 10, 'name' => 'operation manager', 'role_desc' => '-'],
            ['id' => 12, 'name' => 'Marketing', 'role_desc' => '-'],
            ['id' => 13, 'name' => 'Marketing Proenergi', 'role_desc' => 'Role Marketing Agent Proenergi'],
            ['id' => 14, 'name' => 'KAE Proenergi', 'role_desc' => 'Role KAE Agent TDS'],
            ['id' => 15, 'name' => 'Branch Manager Proenergi', 'role_desc' => 'Role BM Agent TDS'],
            ['id' => 16, 'name' => 'OM Proenergi', 'role_desc' => 'Role OM Agent TDS'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                    'role_desc' => $role['role_desc'],
                    'is_active' => true,
                    'created_at' => now(),
                ]
            );
        }

        // insert id eksplisit gak update sequence, jadi di-set manual biar Role::create() berikutnya gak collide.
        DB::statement("SELECT setval('roles_id_role_seq', (SELECT MAX(id) FROM roles))");
    }
}
