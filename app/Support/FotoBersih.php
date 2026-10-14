<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Re-encode foto unggahan laporan lewat GD sebelum disimpan ke disk.
 *
 * Tujuan keamanan: file hasil ekor (JPEG/PNG/WEBP yang di-encode ulang PHP)
 * tidak lagi memuat metadata asli (EXIF — termasuk GPS posisi pelapor),
 * polyglot/ payload tersisip di segment JPEG, dan sekaligus mengecilkan
 * foto beresolusi besar agar hemat disk.
 */
final class FotoBersih
{
    /**
     * Sisi terpanjang maksimum hasil re-encode.
     */
    private const SISI_MAKS = 1600;

    /**
     * Decode file asli, buang seluruh metadata (EXIF), lalu simpan hasil
     * encode ulang ke disk. Mengembalikan path relatif di disk tujuan.
     *
     * @throws RuntimeException bila tipe file bukan JPEG/PNG/WEBP atau GD gagal.
     */
    public static function simpan(UploadedFile $file, string $folder = 'laporan', string $disk = 'public'): string
    {
        $info = getimagesize($file->getRealPath());

        if ($info === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }

        $tipe = $info[2];

        $img = match ($tipe) {
            IMAGETYPE_JPEG => self::dariJpeg($file),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => throw new RuntimeException('Tipe gambar tidak didukung.'),
        };

        if ($img === false) {
            throw new RuntimeException('Gagal memproses gambar.');
        }

        // Kecilkan bila sisi terpanjang melebihi batas (rasio aspek tetap dipertahankan).
        [$lebar, $tinggi] = [imagesx($img), imagesy($img)];
        if (max($lebar, $tinggi) > self::SISI_MAKS) {
            if ($lebar >= $tinggi) {
                $img = imagescale($img, self::SISI_MAKS, -1);
            } else {
                $img = imagescale($img, -1, self::SISI_MAKS);
            }

            if ($img === false) {
                throw new RuntimeException('Gagal mengecilkan gambar.');
            }
        }

        // Encode ulang ke memori: satu-satunya byte yang disimpan adalah hasil
        // encoder-nya sendiri, jadi metadata EXIF dari file asli tidak dibawa.
        try {
            ob_start();

            if ($tipe === IMAGETYPE_JPEG) {
                imagejpeg($img, null, 85);
                $ext = 'jpg';
            } elseif ($tipe === IMAGETYPE_PNG) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                imagepng($img, null, 6);
                $ext = 'png';
            } else {
                imagewebp($img, null, 85);
                $ext = 'webp';
            }

            $isi = (string) ob_get_clean();
        } finally {
            imagedestroy($img);
        }

        $path = $folder.'/'.Str::random(40).'.'.$ext;

        Storage::disk($disk)->put($path, $isi);

        return $path;
    }

    /**
     * Decode JPEG; orientasi EXIF diterapkan menjadi piksel sungguhan (rotate)
     * SEBELUM seluruh metadata dibuang oleh re-encode.
     */
    private static function dariJpeg(UploadedFile $file)
    {
        $img = imagecreatefromjpeg($file->getRealPath());

        if ($img === false) {
            throw new RuntimeException('Gagal memproses gambar JPEG.');
        }

        if (function_exists('exif_read_data') && $info = @exif_read_data($file->getRealPath())) {
            // 3 = putar 180°, 6 = diputar CW 90° saat pengambilan (koreksi: -90),
            // 8 = diputar CCW 90° saat pengambilan (koreksi: +90).
            $sudut = match ((int) ($info['Orientation'] ?? 0)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($sudut !== 0) {
                $putar = imagerotate($img, $sudut, 0);

                if ($putar !== false) {
                    imagedestroy($img);
                    $img = $putar;
                }
            }
        }

        return $img;
    }
}
