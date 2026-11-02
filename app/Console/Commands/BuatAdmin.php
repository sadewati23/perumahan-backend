<?php

namespace App\Console\Commands;

use App\Rules\Username;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BuatAdmin extends Command
{
    protected $signature = 'admin:buat {username} {email} {--nama=Administrator}';

    protected $description = 'Membuat akun administrator produksi secara interaktif';

    public function handle(): int
    {
        $username = strtolower(trim((string) $this->argument('username')));
        $email = strtolower(trim((string) $this->argument('email')));

        if (str_ends_with($email, '@perumahan.test')) {
            $this->error('Email akun bawaan tidak diperbolehkan.');

            return self::FAILURE;
        }

        if (User::where('username', $username)->exists()) {
            $this->error('Username sudah dipakai.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Email tersebut sudah terdaftar.');

            return self::FAILURE;
        }

        $password = $this->secret('Password administrator');
        $confirmation = $this->secret('Konfirmasi password administrator');

        $validator = Validator::make([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'username' => ['required', 'string', new Username],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 12 karakter.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => (string) $this->option('nama'),
            'username' => $username,
            'email' => $email,
            'role' => 'admin',
            'is_active' => true,
            'password' => $password,
        ]);

        $this->info('Administrator berhasil dibuat untuk '.$email.'.');

        return self::SUCCESS;
    }
}
