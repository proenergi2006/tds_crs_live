<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NAME = 'sales-confirmation.view';

    private const ROLE_ID = 2;

    public function up(): void
    {
        if (! DB::table('roles')->exists()) {
            return;
        }

        if (! DB::table('roles')->where('id', self::ROLE_ID)->exists()) {
            throw new RuntimeException('Role id '.self::ROLE_ID.' not found; cannot grant '.self::NAME.'.');
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
                'description' => 'Lihat daftar & detail Sales Confirmation tanpa aksi (CEO)',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('role_has_permissions')->updateOrInsert(
            ['permission_id' => $permissionId, 'role_id' => self::ROLE_ID],
            []
        );

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
