<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const CEO_ROLE_ID = 2;

    private const CEO_GRANTS = [
        'penawaran.verify',
        'penawaran.proenergi.verify',
    ];

    private const REMOVED = [
        'penawaran.verify-bm',
        'penawaran.verify-bm-polimer',
        'penawaran.verify-om',
        'penawaran.verify-ceo',
        'penawaran.proenergi.verify-bm',
        'penawaran.proenergi.verify-om',
    ];

    private const RENAMES = [
        'penawaran.manage' => 'quotation.manage',
        'penawaran.viewAny' => 'quotation.viewAny',
        'penawaran.viewOwn' => 'quotation.viewOwn',
        'penawaran.verify' => 'quotation.verify',
        'penawaran.proenergi.manage' => 'quotation.proenergi.manage',
        'penawaran.proenergi.viewAny' => 'quotation.proenergi.viewAny',
        'penawaran.proenergi.viewOwn' => 'quotation.proenergi.viewOwn',
        'penawaran.proenergi.verify' => 'quotation.proenergi.verify',
    ];

    public function up(): void
    {
        if (! DB::table('permissions')->where('name', 'like', 'penawaran.%')->exists()) {
            return;
        }

        $this->assertNoUnexpectedPermissions();
        $this->assertNoTargetCollision(array_values(self::RENAMES));

        $this->grantCeo();
        $this->removePermissions();
        $this->renamePermissions(self::RENAMES, 'quotation');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $this->assertNoTargetCollision(array_keys(self::RENAMES));

        $this->renamePermissions(array_flip(self::RENAMES), 'penawaran');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function assertNoUnexpectedPermissions(): void
    {
        $known = array_merge(array_keys(self::RENAMES), self::REMOVED);

        $unexpected = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('name', 'like', 'penawaran.%')
            ->whereNotIn('name', $known)
            ->pluck('name')
            ->all();

        if ($unexpected !== []) {
            throw new RuntimeException('Unexpected penawaran.* permissions: '.implode(', ', $unexpected));
        }
    }

    private function assertNoTargetCollision(array $targets): void
    {
        $existing = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $targets)
            ->pluck('name')
            ->all();

        if ($existing !== []) {
            throw new RuntimeException('Target permission names already exist: '.implode(', ', $existing));
        }
    }

    private function grantCeo(): void
    {
        if (! DB::table('roles')->where('id', self::CEO_ROLE_ID)->where('name', 'CEO')->exists()) {
            throw new RuntimeException('Role id '.self::CEO_ROLE_ID.' (CEO) not found.');
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::CEO_GRANTS)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => self::CEO_ROLE_ID],
                []
            );
        }
    }

    private function removePermissions(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::REMOVED)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function renamePermissions(array $map, string $toModule): void
    {
        foreach ($map as $from => $to) {
            DB::table('permissions')
                ->where('guard_name', 'web')
                ->where('name', $from)
                ->update([
                    'name' => $to,
                    'module' => $toModule,
                    'updated_at' => now(),
                ]);
        }
    }
};
