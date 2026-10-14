<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|----------------------------------------------------------------------
| Proxy terpercaya
|----------------------------------------------------------------------
| `TRUSTED_PROXIES` di .env berisi daftar IP/CIDR proxy yang sebenarnya
| (mis. ngrok, Cloudflare, load balancer), dipisah koma.
|
| Bila kosong (default): TIDAK ada proxy dipercaya — header
| X-Forwarded-For / X-Forwarded-Host dari klien langsung diabaikan agar
| tidak bisa dipakai memalsukan IP untuk melewati throttle (login, /lapor,
| lupa password) maupun menyamar sebagai request HTTPS.
|
| Model aplikasi saat ini: di-serve langsung oleh Apache (XAMPP) tanpa
| proxy; tunnel hanya dipakai sesekali — bila aktif, isi IP-nya di .env.
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Daftar proxy terpercaya TIDAK dibaca dari env() di sini, karena
        // callback ini dieksekusi saat kernel di-resolve — masih sebelum .env
        // di-bootstrap (env() selalu null). TrustProxies sendiri yang
        // memanggil config('trustedproxy.proxies') pada saat request diproses,
        // sehingga cukup diisi lewat config/trustedproxy.php.

        $middleware->alias([
            'role'   => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/admin/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
