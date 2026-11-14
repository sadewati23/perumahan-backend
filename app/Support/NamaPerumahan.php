<?php

namespace App\Support;

/**
 * Normalisasi nama perumahan untuk pencegahan duplikat.
 *
 * Aturan yang dipakai konsisten di form, impor, command pemeriksa, dan
 * migration: buang spasi di awal/akhir, rangkum spasi berulang menjadi satu,
 * dan samakan huruf besar/kecil saat membandingkan.
 */
final class NamaPerumahan
{
    public static function normalisasi(?string $nama): ?string
    {
        if ($nama === null) {
            return null;
        }

        // Non-breaking space (umum dari salin-tempel Excel) dianggap spasi biasa.
        $nama = str_replace("\u{00A0}", ' ', $nama);
        $nama = preg_replace('/\s+/u', ' ', $nama) ?? $nama;

        return trim($nama);
    }

    /**
     * Kunci pembanding: normalisasi + huruf kecil (setara collation *_ci).
     */
    public static function kunci(string $nama): string
    {
        return mb_strtolower(self::normalisasi($nama) ?? '', 'UTF-8');
    }

    public static function sama(string $a, string $b): bool
    {
        return self::kunci($a) === self::kunci($b);
    }
}
