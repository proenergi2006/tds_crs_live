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
            ['code' => 'nib', 'name' => 'NIB', 'requires_number' => true, 'category' => 'onboarding'],
            ['code' => 'npwp', 'name' => 'NPWP', 'requires_number' => true, 'category' => 'onboarding'],
            ['code' => 'sertifikat', 'name' => 'Sertifikat', 'requires_number' => true, 'category' => 'onboarding'],
            ['code' => 'dokumen_lainnya', 'name' => 'Dokumen Lainnya', 'requires_number' => false, 'category' => 'onboarding'],

            // 8 kategori foto LCR (id_document_type=lcr_*), category='lcr'.
            // Jangan sampai masuk allow-list dokumen onboarding.
            ['code' => 'lcr_road_condition', 'name' => 'Foto Kondisi Jalan Menuju Lokasi', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_site_layout', 'name' => 'Foto Layout Site/Pabrik', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_unloading_layout', 'name' => 'Foto Layout Area Unloading', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_storage_facility', 'name' => 'Foto Fasilitas Penyimpanan', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_measurement_evidence', 'name' => 'Foto Alat Ukur', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_vessel_layout', 'name' => 'Foto Layout Vessel/Jetty', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_company_office', 'name' => 'Foto Kantor & Gerbang Perusahaan', 'requires_number' => false, 'category' => 'lcr'],
            ['code' => 'lcr_additional', 'name' => 'Foto Tambahan', 'requires_number' => false, 'category' => 'lcr'],
        ];

        foreach ($documentTypes as $type) {
            CustomerDocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name'            => $type['name'],
                    'is_active'       => true,
                    'requires_number' => $type['requires_number'],
                    'category'        => $type['category'] ?? null,
                ]
            );
        }
    }
}
