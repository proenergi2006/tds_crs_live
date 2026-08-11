<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
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
        [
            'name'        => 'admin.users.impersonate',
            'module'      => 'admin',
            'description' => 'Impersonate user lain untuk keperluan debug (masuk sebagai user tsb)',
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
            'name'        => 'master-data.customer-contact-type.manage',
            'module'      => 'master-data',
            'description' => 'Kelola master jenis kontak customer (Direktur, Procurement, Finance, PIC Site, dst)',
            'roles'       => [1],
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
            'name'        => 'verification.po-supplier',
            'module'      => 'verification',
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

        // Customer TDS
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

        // Penawaran TDS
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
            'name'        => 'verification.quotation',
            'module'      => 'verification',
            'description' => 'Akses modul approval penawaran (BM/CFO/CEO/OM)',
            'roles'       => [8, 2, 3, 10],
        ],

        // Penawaran Proenergi (Customer Proenergi kini pakai customer.viewOwn/customer.manage TDS)
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
                    ['permission_id' => $permissionId, 'id_role' => $roleId],
                    []
                );
            }
        }

        $this->cleanupRetiredPermissions();
    }

    private function cleanupRetiredPermissions(): void
    {
        $retiredNames = [
            'penawaran.tds.manage',
            'penawaran.verify-bm',
            'penawaran.verify-om',
            'customer.verify',
            'penawaran.verify',
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
