<?php

namespace Database\Seeders;

use App\Models\CustomerDocumentType;
use Illuminate\Database\Seeder;

class CustomerDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            // category='onboarding' untuk dokumen legal dari Customer Onboarding.
            // Ini jadi allow-list biar kategori lain (mis. lcr) gak nyasar ke section yang salah.
            ['code' => 'nib', 'name' => 'NIB', 'category' => 'onboarding'],
            ['code' => 'npwp', 'name' => 'NPWP', 'category' => 'onboarding'],
            ['code' => 'sertifikat', 'name' => 'Sertifikat', 'category' => 'onboarding'],

            // 8 kategori foto LCR (id_document_type=lcr_*), category='lcr'.
            // Jangan sampai masuk allow-list dokumen onboarding.
            ['code' => 'lcr_road_condition', 'name' => 'Foto Kondisi Jalan Menuju Lokasi', 'category' => 'lcr'],
            ['code' => 'lcr_site_layout', 'name' => 'Foto Layout Site/Pabrik', 'category' => 'lcr'],
            ['code' => 'lcr_unloading_layout', 'name' => 'Foto Layout Area Unloading', 'category' => 'lcr'],
            ['code' => 'lcr_storage_facility', 'name' => 'Foto Fasilitas Penyimpanan', 'category' => 'lcr'],
            ['code' => 'lcr_measurement_evidence', 'name' => 'Foto Alat Ukur', 'category' => 'lcr'],
            ['code' => 'lcr_vessel_layout', 'name' => 'Foto Layout Vessel/Jetty', 'category' => 'lcr'],
            ['code' => 'lcr_company_office', 'name' => 'Foto Kantor & Gerbang Perusahaan', 'category' => 'lcr'],
            ['code' => 'lcr_additional', 'name' => 'Foto Tambahan', 'category' => 'lcr'],
        ];

        foreach ($documentTypes as $type) {
            CustomerDocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name'      => $type['name'],
                    'is_active' => true,
                    'category'  => $type['category'] ?? null,
                ]
            );
        }
    }
}
