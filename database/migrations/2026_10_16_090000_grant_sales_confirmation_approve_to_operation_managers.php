<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NAME = 'sales-confirmation.approve';

    private const ROLE_IDS = [10, 16];

    private const NEW_DESCRIPTION = 'Setujui/tolak Sales Confirmation sebagai BM atau OM sesuai brand penawaran';

    private const OLD_DESCRIPTION = 'Setujui/tolak Sales Confirmation sebagai Branch Manager sesuai brand/produk penawaran';

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

        $permissionId = $this->permissionId();

        if (! $permissionId) {
            throw new RuntimeException('Permission '.self::NAME.' not found; it must exist before it can be granted.');
        }

        DB::table('permissions')->where('id', $permissionId)->update([
            'description' => self::NEW_DESCRIPTION,
            'updated_at' => now(),
        ]);

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
        $permissionId = $this->permissionId();

        if ($permissionId) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->whereIn('role_id', self::ROLE_IDS)
                ->delete();

            DB::table('permissions')->where('id', $permissionId)->update([
                'description' => self::OLD_DESCRIPTION,
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionId(): ?int
    {
        $id = DB::table('permissions')
            ->where('name', self::NAME)
            ->where('guard_name', 'web')
            ->value('id');

        return $id ? (int) $id : null;
    }
};
