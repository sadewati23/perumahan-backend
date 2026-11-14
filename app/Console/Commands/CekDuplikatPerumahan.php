<?php

namespace App\Console\Commands;

use App\Models\Perumahan;
use App\Support\NamaPerumahan;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Pemeriksa duplikat perumahan (nama + kelurahan_id), baca-saja.
 *
 * Pembanding memakai aturan yang sama dengan form & impor: huruf besar/kecil
 * tidak dibedakan dan spasi berlebih diabaikan. Command tidak menulis apa pun;
 * exit code non-nol dipakai sebagai rambu sebelum migration unique dijalankan.
 */
class CekDuplikatPerumahan extends Command
{
    protected $signature = 'perumahan:cek-duplikat';

    protected $description = 'Melaporkan perumahan duplikat (nama + kelurahan) tanpa mengubah data.';

    public function handle(): int
    {
        $perumahans = Perumahan::query()
            ->with('kelurahan.kecamatan')
            ->withCount('laporans')
            ->orderBy('id')
            ->get();

        $kelompok = $perumahans
            ->groupBy(fn (Perumahan $p): string => NamaPerumahan::kunci($p->nama).'|'.$p->kelurahan_id)
            ->filter(fn (Collection $grup): bool => $grup->count() > 1);

        if ($kelompok->isEmpty()) {
            $this->info(sprintf(
                'Tidak ada duplikat. %d data perumahan diperiksa.',
                $perumahans->count(),
            ));

            return self::SUCCESS;
        }

        $jumlahBaris = $kelompok->sum(fn (Collection $grup): int => $grup->count());

        $this->warn(sprintf(
            'Ditemukan %d kelompok perumahan duplikat (%d baris). Tidak ada data yang diubah.',
            $kelompok->count(),
            $jumlahBaris,
        ));
        $this->newLine();

        $baris = [];
        $nomor = 0;

        foreach ($kelompok as $grup) {
            $nomor++;
            foreach ($grup as $perumahan) {
                $baris[] = [
                    '#'.$nomor,
                    $perumahan->id,
                    $perumahan->nama,
                    $perumahan->kelurahan?->nama_lengkap ?? ('kelurahan_id '.$perumahan->kelurahan_id),
                    $perumahan->laporans_count,
                ];
            }
        }

        $this->table(['Kelompok', 'ID', 'Nama', 'Kelurahan', 'Jumlah laporan'], $baris);

        $this->newLine();
        $this->line('Rapikan data secara manual (gabungkan atau ubah nama), lalu jalankan ulang command ini sebelum migrate.');

        return self::FAILURE;
    }
}
