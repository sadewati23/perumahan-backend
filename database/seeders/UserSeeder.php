<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Seeder ini hanya untuk pengembangan lokal dan dilarang di produksi.');

            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@perumahan.test'],
            [
                'username' => 'admin',
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
                'username' => 'petugas',
                'name' => 'Petugas Pendataan',
                'role' => 'petugas',
                'jabatan' => 'Surveyor Lapangan',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );
    }
}
