<?php

/*
|--------------------------------------------------------------------------
| Proxy terpercaya (ngrok, load balancer, Cloudflare, dst.)
|--------------------------------------------------------------------------
| Nilai TRUSTED_PROXIES di .env berisi daftar IP/CIDR yang dipisah koma;
| gunakan `*` hanya untuk tunnel pengembangan. Nilai `*` diabaikan pada
| produksi. Bila kosong: tidak ada proxy dipercaya.
|
| Catatan: nilai ini harus dibaca lewat config (file ini), bukan langsung
| env() di bootstrap/app.php — callback withMiddleware() dieksekusi
| sebelum .env di-bootstrap, sehingga env() di situ selalu null.
| Illuminate\Http\Middleware\TrustProxies memanggil
| config('trustedproxy.proxies') saat request diproses, di mana .env
| sudah ter-load.
*/

$trustedProxies = env('TRUSTED_PROXIES', '');

if (env('APP_ENV') === 'production' && in_array('*', array_map('trim', explode(',', $trustedProxies)), true)) {
    $trustedProxies = '';
}

return [
    'proxies' => $trustedProxies === ''
        ? null
        : array_values(array_filter(
            array_map(fn (string $ip) => trim($ip), explode(',', $trustedProxies)),
        )),
];
