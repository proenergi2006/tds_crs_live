<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// roles gak pernah punya seeder di repo ini -- id_role dipakai eksplisit di banyak tempat
// (PermissionSeeder, kode aplikasi), jadi ID di sini harus sama persis, bukan auto-increment bebas.
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id_role' => 1, 'role_name' => 'Administrator', 'role_desc' => null],
            ['id_role' => 2, 'role_name' => 'CEO', 'role_desc' => '-'],
            ['id_role' => 3, 'role_name' => 'CFO', 'role_desc' => '-'],
            ['id_role' => 4, 'role_name' => 'Key Account', 'role_desc' => '-'],
            ['id_role' => 5, 'role_name' => 'Procurement', 'role_desc' => '-'],
            ['id_role' => 6, 'role_name' => 'Logistik', 'role_desc' => '-'],
            ['id_role' => 7, 'role_name' => 'Logistik HO', 'role_desc' => '-'],
            ['id_role' => 8, 'role_name' => 'branch manager', 'role_desc' => '-'],
            ['id_role' => 9, 'role_name' => 'admin finance', 'role_desc' => '-'],
            ['id_role' => 10, 'role_name' => 'operation manager', 'role_desc' => '-'],
            ['id_role' => 12, 'role_name' => 'Marketing', 'role_desc' => '-'],
            ['id_role' => 13, 'role_name' => 'Marketing Proenergi', 'role_desc' => 'Role Marketing Agent Proenergi'],
            ['id_role' => 14, 'role_name' => 'KAE Proenergi', 'role_desc' => 'Role KAE Agent TDS'],
            ['id_role' => 15, 'role_name' => 'Branch Manager Proenergi', 'role_desc' => 'Role BM Agent TDS'],
            ['id_role' => 16, 'role_name' => 'OM Proenergi', 'role_desc' => 'Role OM Agent TDS'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id_role' => $role['id_role']],
                [
                    'role_name' => $role['role_name'],
                    'role_desc' => $role['role_desc'],
                    'is_active' => true,
                    'created_time' => now(),
                    'created_by' => 'seeder',
                ]
            );
        }

        // insert id_role eksplisit gak ngegeser sequence auto-increment-nya -- geser manual
        // biar Role::create() berikutnya (di test/kode lain) gak collide sama id yang udah dipakai.
        DB::statement("SELECT setval('roles_id_role_seq', (SELECT MAX(id_role) FROM roles))");
    }
}
