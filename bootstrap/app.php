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
        // Hanya IP proxy sebenarnya yang boleh memengaruhi $request->ip(),
        // scheme, dan host — dipetakan dari env TRUSTED_PROXIES.
        $trustedProxies = array_values(array_filter(array_map(
            fn (string $ip) => trim($ip),
            explode(',', (string) env('TRUSTED_PROXIES', '')),
        )));

        $middleware->trustProxies(at: $trustedProxies);

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
