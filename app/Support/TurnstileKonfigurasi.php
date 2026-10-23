<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class TurnstileKonfigurasi
{
    public static function kunciUji(?string $kunci): bool
    {
        return is_string($kunci) && preg_match('/^[123]x0{10,}[A-Z]{2}$/', $kunci) === 1;
    }

    public static function kunciUjiTerpasang(): bool
    {
        $turnstile = config('services.turnstile', []);

        return self::kunciUji($turnstile['site_key'] ?? null)
            || self::kunciUji($turnstile['secret'] ?? null);
    }

    public static function aktif(): bool
    {
        $secret = config('services.turnstile.secret');

        return filled($secret)
            && ! (app()->isProduction() && self::kunciUjiTerpasang());
    }

    public static function tampilkanWidget(): bool
    {
        return self::aktif() && filled(config('services.turnstile.site_key'));
    }

    public static function perluPerhatian(): bool
    {
        return app()->isProduction()
            && (
                blank(config('services.turnstile.secret'))
                || blank(config('services.turnstile.site_key'))
                || self::kunciUjiTerpasang()
            );
    }

    public static function catat(): void
    {
        if (! app()->isProduction() || ! self::kunciUjiTerpasang()) {
            return;
        }

        if (Cache::add('turnstile.kunci_uji.dilaporkan', true, now()->addHour())) {
            Log::critical('turnstile.kunci_uji_di_produksi', [
                'akibat' => 'CAPTCHA dinonaktifkan',
            ]);
        }
    }
}
