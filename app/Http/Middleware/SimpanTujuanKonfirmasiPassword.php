<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpanTujuanKonfirmasiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 900);

        if ($request->isMethod('GET') === false && time() - $confirmedAt > $timeout) {
            $request->session()->put('konfirmasi_password.url', $request->fullUrl());
        }

        return $next($request);
    }
}
