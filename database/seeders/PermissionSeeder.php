<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar 23 permission + mapping ke id_role.
     * Format name: module.action (kebab-case).
     * Mapping dari audit meta.roles di router/index.ts + roleMenuMapping.ts.
     */
    private array $permissions = [
        // Tata Kelola
        [
            'name'        => 'admin.users.manage',
            'module'      => 'admin',
            'description' => 'Kelola user dan role (Access Control)',
            'roles'       => [1],
        ],
        [
            'name'        => 'admin.monitoring.view',
            'module'      => 'admin',
            'description' => 'Lihat application logs (Monitoring)',
            'roles'       => [1],
        ],

        // Master Data
        [
            'name'        => 'master-data.view',
            'module'      => 'master-data',
            'description' => 'Lihat master data (Produk, Satuan, Ukuran, Jenis, Vendor, Terminal)',
            'roles'       => [2, 5],
        ],
        [
            'name'        => 'master-data.wilayah.manage',
            'module'      => 'master-data',
            'description' => 'Kelola data wilayah (Provinsi, Kabupaten)',
            'roles'       => [1, 2, 8],
        ],
        [
            'name'        => 'master-data.cabang.manage',
            'module'      => 'master-data',
            'description' => 'Kelola data cabang',
            'roles'       => [2],
        ],
        [
            'name'        => 'master-data.vendor.manage',
            'module'      => 'master-data',
            'description' => 'Tambah / edit vendor',
            'roles'       => [5],
        ],

        // Harga Produk
        [
            'name'        => 'harga-produk.view',
            'module'      => 'harga-produk',
            'description' => 'Lihat dan edit harga produk & attachment harga',
            'roles'       => [2, 5, 8],
        ],
        [
            'name'        => 'harga-produk.manage',
            'module'      => 'harga-produk',
            'description' => 'Tambah harga produk baru (create)',
            'roles'       => [5],
        ],

        // PO Supplier / Procurement
        [
            'name'        => 'po-supplier.manage',
            'module'      => 'po-supplier',
            'description' => 'Buat, edit, lihat, dan terima PO Supplier',
            'roles'       => [5],
        ],
        [
            'name'        => 'po-supplier.verify',
            'module'      => 'po-supplier',
            'description' => 'Verifikasi / approve PO Supplier (CFO & CEO)',
            'roles'       => [2, 3],
        ],
        [
            'name'        => 'good-receipt.manage',
            'module'      => 'good-receipt',
            'description' => 'Kelola Good Receipt (penerimaan barang)',
            'roles'       => [5],
        ],
        [
            'name'        => 'inventory.view',
            'module'      => 'inventory',
            'description' => 'Lihat stock inventory',
            'roles'       => [5],
        ],
        [
            'name'        => 'delivery-request.manage',
            'module'      => 'delivery-request',
            'description' => 'Kelola Delivery Request (procurement)',
            'roles'       => [5],
        ],

        // Customer & Penawaran TDS
        [
            'name'        => 'penawaran.tds.manage',
            'module'      => 'penawaran',
            'description' => 'Kelola Customer TDS dan Penawaran TDS',
            'roles'       => [4, 12],
        ],
        [
            'name'        => 'penawaran.verify-bm',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran sebagai Branch Manager (BM TDS)',
            'roles'       => [8],
        ],
        [
            'name'        => 'penawaran.verify-om',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran sebagai OM / CEO / CFO (TDS)',
            'roles'       => [2, 3, 10],
        ],

        // Customer & Penawaran Proenergi
        [
            'name'        => 'penawaran.proenergi.manage',
            'module'      => 'penawaran',
            'description' => 'Kelola Customer Proenergi dan Penawaran Proenergi',
            'roles'       => [13, 14],
        ],
        [
            'name'        => 'penawaran.proenergi.verify-bm',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran Proenergi sebagai BM Proenergi',
            'roles'       => [15],
        ],
        [
            'name'        => 'penawaran.proenergi.verify-om',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran Proenergi sebagai OM Proenergi',
            'roles'       => [16],
        ],

        // Sales Confirmation
        [
            'name'        => 'sales-confirmation.manage',
            'module'      => 'sales-confirmation',
            'description' => 'Kelola Sales Confirmation dan Review Data Customer (Admin Finance)',
            'roles'       => [9],
        ],

        // Logistik
        [
            'name'        => 'logistik.lcr.verify',
            'module'      => 'logistik',
            'description' => 'Verifikasi LCR (Logistik cabang)',
            'roles'       => [6],
        ],
        [
            'name'        => 'logistik.master.manage',
            'module'      => 'logistik',
            'description' => 'Kelola master logistik: Transportir, Personnel, Volume, Wilayah Angkut, Kapal, Truck, OA',
            'roles'       => [7],
        ],
        [
            'name'        => 'logistik.delivery-plan.view',
            'module'      => 'logistik',
            'description' => 'Lihat dan kelola Delivery Plan (Logistik HO)',
            'roles'       => [7],
        ],
    ];

    public function run(): void
    {
        $now = now();

        foreach ($this->permissions as $perm) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'        => $perm['name'],
                'guard_name'  => 'web',
                'module'      => $perm['module'],
                'description' => $perm['description'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $pivotRows = array_map(
                fn ($roleId) => ['permission_id' => $permissionId, 'id_role' => $roleId],
                $perm['roles']
            );

            DB::table('role_has_permissions')->insert($pivotRows);
        }
    }
}
