<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Administrator',
            'email' => 'iwan.hermawan@proenergi.co.id',
            'password' => Hash::make('secret123'), // ganti password-nya sesuai kebutuhan
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
