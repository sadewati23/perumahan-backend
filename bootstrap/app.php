<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

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
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Laravel melewati TrustHosts pada unit test. Kondisi eksplisit ini
        // juga mencegah state trusted-host vendor terbawa antar test ketika
        // test mengubah environment aplikasi menjadi production.
        if (! app()->runningUnitTests()) {
            $middleware->trustHosts(
                at: fn () => \App\Support\HostTepercaya::daftar(),
                subdomains: false,
            );
        }
        // Host dan prefix forwarded tidak dipercaya agar klien tidak dapat
        // memalsukan origin URL atau base path aplikasi.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->authenticateSessions();

        // Daftar proxy terpercaya TIDAK dibaca dari env() di sini, karena
        // callback ini dieksekusi saat kernel di-resolve — masih sebelum .env
        // di-bootstrap (env() selalu null). TrustProxies sendiri yang
        // memanggil config('trustedproxy.proxies') pada saat request diproses,
        // sehingga cukup diisi lewat config/trustedproxy.php.

        $middleware->alias([
            'role'   => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'tanpa-cache' => \App\Http\Middleware\TanpaCache::class,
            'simpan-tujuan-konfirmasi' => \App\Http\Middleware\SimpanTujuanKonfirmasiPassword::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/admin/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withInput()->withErrors([
                'foto' => 'Ukuran total unggahan melebihi batas server ('.ini_get('post_max_size').'). Kecilkan ukuran foto lalu coba lagi.',
            ]);
        });
    })->create();
