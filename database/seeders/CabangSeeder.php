<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data cabang asli perusahaan (bukan data customer/PII) -- id & nama disamakan
 * dengan yang dipakai di live supaya id_cabang di seeder lain konsisten.
 */
class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $cabangs = [
            ['id_cabang' => 8, 'nama_cabang' => 'Head Office', 'inisial_cabang' => 'H0', 'inisial_segel' => 'SG-HO'],
            ['id_cabang' => 9, 'nama_cabang' => 'Palu', 'inisial_cabang' => 'PL', 'inisial_segel' => 'SG-PL'],
            ['id_cabang' => 10, 'nama_cabang' => 'Samarinda', 'inisial_cabang' => 'SMD', 'inisial_segel' => 'SG-SMD'],
            ['id_cabang' => 11, 'nama_cabang' => 'Jakarta', 'inisial_cabang' => 'JKT', 'inisial_segel' => 'SG-JKT'],
            ['id_cabang' => 12, 'nama_cabang' => 'Lampung', 'inisial_cabang' => 'LM', 'inisial_segel' => 'SG-LM'],
            ['id_cabang' => 13, 'nama_cabang' => 'Palembang', 'inisial_cabang' => 'PLB', 'inisial_segel' => 'SG-PLB'],
        ];

        foreach ($cabangs as $cabang) {
            DB::table('cabangs')->updateOrInsert(
                ['id_cabang' => $cabang['id_cabang']],
                array_merge($cabang, [
                    'is_active' => true,
                    'created_time' => now(),
                    'created_by' => 'seeder',
                    'catatan_cabang' => null,
                ])
            );
        }
    }
}
