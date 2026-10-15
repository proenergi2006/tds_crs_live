<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NAME = 'quotation.verify';

    private const ROLE_ID = 3;

    public function up(): void
    {
        if (! DB::table('roles')->exists()) {
            return;
        }

        $permissionId = $this->permissionId();

        if ($permissionId) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', self::ROLE_ID)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = $this->permissionId();

        if ($permissionId && DB::table('roles')->where('id', self::ROLE_ID)->exists()) {
            DB::table('role_has_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => self::ROLE_ID],
                []
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where('name', self::NAME)
            ->where('guard_name', 'web')
            ->value('id');
    }
};
