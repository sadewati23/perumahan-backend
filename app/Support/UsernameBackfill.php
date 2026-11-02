<?php

namespace App\Support;

use Illuminate\Support\Str;

final class UsernameBackfill
{
    public static function dari(string $email, array $terpakai): string
    {
        $lokal = Str::lower((string) Str::before($email, '@'));
        $dasar = preg_replace('/[^a-z0-9._-]+/', '.', $lokal) ?? '';
        $dasar = preg_replace('/[._-]+/', '.', $dasar) ?? '';
        $dasar = trim($dasar, '.-_');

        if (strlen($dasar) < 3) {
            $dasar = 'user'.$dasar;
        }

        $dasar = trim(substr($dasar, 0, 30), '.-_');
        if (strlen($dasar) < 3) {
            $dasar = 'user';
        }

        $terpakai = array_map(static fn ($username): string => Str::lower((string) $username), $terpakai);
        $kandidat = $dasar;
        $nomor = 2;

        while (in_array($kandidat, $terpakai, true)) {
            $suffix = (string) $nomor++;
            $kandidat = substr($dasar, 0, 30 - strlen($suffix)).$suffix;
        }

        return $kandidat;
    }
}
