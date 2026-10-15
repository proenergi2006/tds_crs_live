<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NAME = 'sales-confirmation.approve';

    private const ROLE_IDS = [8, 15, 17];

    public function up(): void
    {
        if (! DB::table('roles')->exists()) {
            return;
        }

        foreach (self::ROLE_IDS as $roleId) {
            if (! DB::table('roles')->where('id', $roleId)->exists()) {
                throw new RuntimeException('Role id '.$roleId.' not found; cannot grant '.self::NAME.'.');
            }
        }

        $now = now();

        $permissionId = DB::table('permissions')
            ->where('name', self::NAME)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => self::NAME,
                'guard_name' => 'web',
                'module' => 'sales-confirmation',
                'description' => 'Setujui/tolak Sales Confirmation sebagai Branch Manager sesuai brand/produk penawaran',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (self::ROLE_IDS as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                []
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', self::NAME)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
