<?php

namespace App\Support;

final class HostTepercaya
{
    /** @return list<string> */
    public static function daftar(): array
    {
        if (! app()->isProduction()) {
            return [];
        }

        $hosts = [];
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = strtolower($appHost);
        }

        foreach (config('app.trusted_hosts', []) as $host) {
            $host = strtolower(trim($host));
            $host = preg_replace('/:\d+$/', '', $host) ?? $host;

            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique(array_map(
            static fn (string $host): string => '^'.preg_quote($host, '/').'$',$hosts
        )));
    }
}
