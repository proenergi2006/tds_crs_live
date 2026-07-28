<?php

namespace Database\Seeders;

use App\Models\CustomerContactType;
use Illuminate\Database\Seeder;

class CustomerContactTypeSeeder extends Seeder
{
    public function run(): void
    {
        $contactTypes = [
            ['code' => 'director', 'name' => 'Direktur'],
            ['code' => 'procurement', 'name' => 'Procurement'],
            ['code' => 'finance', 'name' => 'Finance'],
            ['code' => 'site_pic', 'name' => 'PIC Site'],
        ];

        foreach ($contactTypes as $type) {
            CustomerContactType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name'      => $type['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
