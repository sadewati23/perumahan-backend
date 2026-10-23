<?php

namespace App\Rules;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use App\Support\TurnstileKonfigurasi;

class TurnstileTerverifikasi implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $secret = config('services.turnstile.secret');
        if (! TurnstileKonfigurasi::aktif()) return;

        try {
            $hasil = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $secret, 'response' => (string) $value, 'remoteip' => request()->ip()]
            )->json();
        } catch (ConnectionException) {
            $fail('Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba lagi.');
            return;
        }

        if (! is_array($hasil) || ($hasil['success'] ?? false) !== true) {
            $fail('Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba lagi.');
        }
    }
}
