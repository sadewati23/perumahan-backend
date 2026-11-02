<?php

namespace App\Support;

final class OriginAplikasi
{
    public static function url(): ?string
    {
        if (! app()->isProduction()) {
            return null;
        }

        $origin = rtrim((string) config('app.url'), '/');
        $host = parse_url($origin, PHP_URL_HOST);

        if (! is_string($host) || self::hostLokal($host)) {
            return null;
        }

        return $origin;
    }

    private static function hostLokal(string $host): bool
    {
        $host = strtolower($host);

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.localhost');
    }
}
