<?php

namespace Database\Seeders;

use App\Models\CustomerDocumentType;
use Illuminate\Database\Seeder;

class CustomerDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            ['code' => 'nib', 'name' => 'NIB', 'requires_number' => true],
            ['code' => 'npwp', 'name' => 'NPWP', 'requires_number' => true],
            ['code' => 'sertifikat', 'name' => 'Sertifikat', 'requires_number' => true],
            ['code' => 'dokumen_lainnya', 'name' => 'Dokumen Lainnya', 'requires_number' => false],
        ];

        foreach ($documentTypes as $type) {
            CustomerDocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name'            => $type['name'],
                    'is_active'       => true,
                    'requires_number' => $type['requires_number'],
                ]
            );
        }
    }
}
