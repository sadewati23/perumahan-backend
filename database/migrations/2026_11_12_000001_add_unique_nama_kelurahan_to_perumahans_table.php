<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique index (nama, kelurahan_id) untuk mencegah perumahan ganda.
 *
 * Guard di up() sengaja mandiri (tanpa model/aplikasi class) sesuai praktik
 * migration: bila masih ada duplikat, migration berhenti TANPA mengubah apa pun
 * dan meminta data dirapikan lebih dulu lewat `php artisan perumahan:cek-duplikat`.
 *
 * Batas panjang index MySQL/MariaDB (utf8mb4): nama varchar(255) = 255 x 4 =
 * 1020 byte + kelurahan_id bigint 8 byte = 1028 byte, masih di bawah batas
 * 3072 byte untuk InnoDB (DYNAMIC row format). Tidak perlu memotong kolom.
 */
return new class extends Migration
{
    private const NAMA_INDEX = 'perumahans_nama_kelurahan_unique';

    public function up(): void
    {
        $rows = DB::table('perumahans')
            ->select('id', 'nama', 'kelurahan_id')
            ->orderBy('id')
            ->get();

        $grup = [];
        foreach ($rows as $row) {
            // Normalisasi sama dengan aplikasi: NBSP -> spasi, spasi berulang -> satu,
            // trim, lalu samakan huruf besar/kecil.
            $nama = str_replace("\u{00A0}", ' ', (string) $row->nama);
            $nama = preg_replace('/\s+/u', ' ', $nama) ?? $nama;
            $nama = trim($nama);

            $kunci = mb_strtolower($nama, 'UTF-8').'|'.$row->kelurahan_id;

            if (! isset($grup[$kunci])) {
                $grup[$kunci] = [
                    'nama' => $nama,
                    'kelurahan_id' => $row->kelurahan_id,
                    'ids' => [],
                ];
            }

            $grup[$kunci]['ids'][] = $row->id;
        }

        $duplikat = array_filter($grup, fn (array $item): bool => count($item['ids']) > 1);

        if ($duplikat !== []) {
            $contoh = [];
            foreach (array_slice($duplikat, 0, 5, true) as $item) {
                $contoh[] = sprintf(
                    '"%s" (kelurahan_id %s; id: %s)',
                    $item['nama'],
                    $item['kelurahan_id'],
                    implode(', ', $item['ids']),
                );
            }

            throw new RuntimeException(
                'Migration dibatalkan: masih ada '.count($duplikat).' kelompok perumahan duplikat berdasarkan (nama, kelurahan_id). '
                .'Tidak ada perubahan skema yang dilakukan. Jalankan `php artisan perumahan:cek-duplikat`, '
                .'rapikan data tersebut secara manual, lalu jalankan ulang migration. '
                .'Contoh: '.implode('; ', $contoh)
            );
        }

        Schema::table('perumahans', function (Blueprint $table): void {
            $table->unique(['nama', 'kelurahan_id'], self::NAMA_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('perumahans', function (Blueprint $table): void {
            $table->dropUnique(self::NAMA_INDEX);
        });
    }
};
