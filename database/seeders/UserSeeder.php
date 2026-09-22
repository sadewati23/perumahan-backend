<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@perumahan.test'],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'jabatan' => 'Kepala Bidang Perumahan',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'petugas@perumahan.test'],
            [
                'name' => 'Petugas Pendataan',
                'role' => 'petugas',
                'jabatan' => 'Surveyor Lapangan',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );
    }
}
