<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TanpaCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! str_contains(strtolower((string) $response->headers->get('Cache-Control')), 'no-store')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
