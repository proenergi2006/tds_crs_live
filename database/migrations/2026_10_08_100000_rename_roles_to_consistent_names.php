<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const RENAMES = [
        6 => ['Logistik', 'Logistic Branch'],
        7 => ['Logistik HO', 'Logistic HO'],
        8 => ['branch manager', 'Branch Manager'],
        9 => ['admin finance', 'Admin Finance'],
        10 => ['operation manager', 'Operation Manager'],
        14 => ['KAE Proenergi', 'Key Account Proenergi'],
        16 => ['OM Proenergi', 'Operation Manager Proenergi'],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $id => [$old, $new]) {
            DB::table('roles')->where('id', $id)->where('name', $old)->update(['name' => $new]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::RENAMES as $id => [$old, $new]) {
            DB::table('roles')->where('id', $id)->where('name', $new)->update(['name' => $old]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
