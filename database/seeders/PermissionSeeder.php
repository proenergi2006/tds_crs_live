<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    private array $permissions = [
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
        [
            'name'        => 'admin.users.impersonate',
            'module'      => 'admin',
            'description' => 'Impersonate user lain untuk keperluan debug (masuk sebagai user tsb)',
            'roles'       => [1],
        ],

        [
            'name'        => 'master-data.view',
            'module'      => 'master-data',
            'description' => 'Lihat master data (Produk, Satuan, Ukuran, Jenis, Vendor, Terminal)',
            'roles'       => [2, 5],
        ],
        [
            'name'        => 'master-data.cabang.manage',
            'module'      => 'master-data',
            'description' => 'Kelola data cabang',
            'roles'       => [2],
        ],
        [
            'name'        => 'master-data.address.view',
            'module'      => 'master-data',
            'description' => 'Lihat halaman Master Address (province/regency/district/village BPS)',
            'roles'       => [1],
        ],
        [
            'name'        => 'master-data.vendor.manage',
            'module'      => 'master-data',
            'description' => 'Tambah / edit vendor',
            'roles'       => [5],
        ],
        [
            'name'        => 'approval-template.manage',
            'module'      => 'approval-template',
            'description' => 'Kelola master data approval template/step',
            'roles'       => [1],
        ],
        [
            'name'        => 'master-data.customer-document-type.manage',
            'module'      => 'master-data',
            'description' => 'Kelola master jenis dokumen customer (NIB, NPWP, Sertifikat, dst)',
            'roles'       => [1],
        ],

        [
            'name'        => 'harga-produk.view',
            'module'      => 'harga-produk',
            'description' => 'Lihat dan edit harga produk & attachment harga',
            'roles'       => [2, 5, 8, 10],
        ],
        [
            'name'        => 'harga-produk.manage',
            'module'      => 'harga-produk',
            'description' => 'Tambah harga produk baru (create)',
            'roles'       => [2, 5],
        ],
        [
            'name'        => 'harga-produk.set-cogs',
            'module'      => 'harga-produk',
            'description' => 'Isi kolom COGS harga produk (Procurement)',
            'roles'       => [5],
        ],
        [
            'name'        => 'harga-produk.set-price-list',
            'module'      => 'harga-produk',
            'description' => 'Lengkapi kolom harga (price list, margin, BM, OM, CEO) sebagai finalisasi periode harga',
            'roles'       => [2],
        ],

        [
            'name'        => 'po-supplier.manage',
            'module'      => 'po-supplier',
            'description' => 'Buat, edit, lihat, dan terima PO Supplier',
            'roles'       => [5],
        ],
        [
            'name'        => 'verification.po-supplier',
            'module'      => 'verification',
            'description' => 'Verifikasi / approve PO Supplier (CFO & CEO)',
            'roles'       => [2, 3],
        ],
        [
            'name'        => 'po-supplier.verify-cfo',
            'module'      => 'po-supplier',
            'description' => 'Verifikasi PO Supplier sebagai CFO',
            'roles'       => [3],
        ],
        [
            'name'        => 'po-supplier.verify-ceo',
            'module'      => 'po-supplier',
            'description' => 'Verifikasi PO Supplier sebagai CEO',
            'roles'       => [2],
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
            'roles'       => [2, 5],
        ],
        [
            'name'        => 'delivery-request.manage',
            'module'      => 'delivery-request',
            'description' => 'Kelola Delivery Request (procurement)',
            'roles'       => [5],
        ],

        [
            'name'        => 'customer.viewOwn',
            'module'      => 'customer',
            'description' => 'Lihat customer milik sendiri (Marketing/Key Account)',
            'roles'       => [4, 12, 13, 14],
        ],
        [
            'name'        => 'customer.viewAny',
            'module'      => 'customer',
            'description' => 'Lihat semua customer lintas-marketing (Administrator/Admin Finance/BM/OM, CEO merangkap OM)',
            'roles'       => [1, 9, 8, 10, 2],
        ],
        [
            'name'        => 'customer.manage',
            'module'      => 'customer',
            'description' => 'Tambah, edit, hapus customer',
            'roles'       => [4, 12, 13, 14],
        ],
        [
            'name'        => 'verification.customer',
            'module'      => 'verification',
            'description' => 'Akses modul verifikasi customer (BM & Admin Finance)',
            'roles'       => [8, 9],
        ],
        [
            'name'        => 'admin.customer-migration.manage',
            'module'      => 'admin',
            'description' => 'Migrasi kepemilikan customer antar-marketing',
            'roles'       => [1],
        ],

        [
            'name'        => 'penawaran.viewOwn',
            'module'      => 'penawaran',
            'description' => 'Lihat penawaran milik sendiri (Marketing/Key Account)',
            'roles'       => [4, 12],
        ],
        [
            'name'        => 'penawaran.viewAny',
            'module'      => 'penawaran',
            'description' => 'Lihat semua penawaran lintas-marketing (Administrator/Admin Finance/BM/OM, CEO merangkap OM)',
            'roles'       => [1, 9, 8, 10, 2],
        ],
        [
            'name'        => 'penawaran.manage',
            'module'      => 'penawaran',
            'description' => 'Tambah, edit, hapus penawaran',
            'roles'       => [4, 12],
        ],
        [
            'name'        => 'penawaran.verify',
            'module'      => 'penawaran',
            'description' => 'Akses modul approval penawaran (BM/CFO/OM)',
            'roles'       => [8, 3, 10],
        ],
        [
            'name'        => 'penawaran.verify-bm',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran TDS sebagai Branch Manager',
            'roles'       => [8],
        ],
        [
            'name'        => 'penawaran.verify-om',
            'module'      => 'penawaran',
            'description' => 'Verifikasi Penawaran TDS sebagai Operation Manager',
            'roles'       => [10],
        ],

        [
            'name'        => 'penawaran.proenergi.viewOwn',
            'module'      => 'penawaran',
            'description' => 'Lihat penawaran Proenergi milik sendiri (Marketing/KAE Proenergi)',
            'roles'       => [13, 14],
        ],
        [
            'name'        => 'penawaran.proenergi.viewAny',
            'module'      => 'penawaran',
            'description' => 'Lihat semua penawaran Proenergi lintas-marketing (Administrator/Admin Finance/CEO/BM/OM Proenergi)',
            'roles'       => [1, 9, 2, 15, 16],
        ],
        [
            'name'        => 'penawaran.proenergi.manage',
            'module'      => 'penawaran',
            'description' => 'Kelola Penawaran Proenergi',
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
        [
            'name'        => 'penawaran.proenergi.verify',
            'module'      => 'penawaran',
            'description' => 'Akses menu Verifikasi Quotation Proenergi (gabungan BM & OM, data dibedakan per tahap)',
            'roles'       => [15, 16],
        ],

        [
            'name'        => 'sales-confirmation.manage',
            'module'      => 'sales-confirmation',
            'description' => 'Kelola Sales Confirmation dan Review Data Customer (Admin Finance)',
            'roles'       => [9],
        ],

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

        [
            'name'        => 'dashboard.view-ceo',
            'module'      => 'dashboard',
            'description' => 'Akses dashboard ringkasan CEO',
            'roles'       => [2],
        ],
        [
            'name'        => 'dashboard.view-om',
            'module'      => 'dashboard',
            'description' => 'Akses dashboard ringkasan Operation Manager',
            'roles'       => [10],
        ],
    ];

    public function run(): void
    {
        $now = now();

        $this->renameLegacyPermissionNames();

        foreach ($this->permissions as $perm) {
            $permissionId = DB::table('permissions')
                ->where('name', $perm['name'])
                ->where('guard_name', 'web')
                ->value('id');

            if ($permissionId) {
                DB::table('permissions')->where('id', $permissionId)->update([
                    'module'      => $perm['module'],
                    'description' => $perm['description'],
                    'updated_at'  => $now,
                ]);
            } else {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name'        => $perm['name'],
                    'guard_name'  => 'web',
                    'module'      => $perm['module'],
                    'description' => $perm['description'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            foreach ($perm['roles'] as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $roleId],
                    []
                );
            }
        }

        $this->cleanupRetiredPermissions();
        $this->cleanupRevokedRolePermissions();
    }

    private function renameLegacyPermissionNames(): void
    {
        $renames = [
            'verification.quotation' => 'penawaran.verify',
        ];

        foreach ($renames as $from => $to) {
            DB::table('permissions')
                ->where('name', $from)
                ->where('guard_name', 'web')
                ->update(['name' => $to, 'updated_at' => now()]);
        }
    }

    private function cleanupRevokedRolePermissions(): void
    {
        $revoked = [
            ['permission' => 'penawaran.verify', 'role_id' => 2],
        ];

        foreach ($revoked as $entry) {
            $permissionId = DB::table('permissions')
                ->where('name', $entry['permission'])
                ->where('guard_name', 'web')
                ->value('id');

            if ($permissionId) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $entry['role_id'])
                    ->delete();
            }
        }
    }

    private function cleanupRetiredPermissions(): void
    {
        $retiredNames = [
            'penawaran.tds.manage',
            'customer.verify',
            'po-supplier.verify',
            'master-data.wilayah.manage',
        ];

        $retiredIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $retiredNames)
            ->pluck('id');

        if ($retiredIds->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $retiredIds)
                ->delete();
        }

        DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $retiredNames)
            ->delete();
    }
}
