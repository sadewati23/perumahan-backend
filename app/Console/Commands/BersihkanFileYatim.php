<?php

namespace App\Console\Commands;

use App\Models\LaporanFoto;
use App\Models\Perumahan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Melaporkan (dan bila diminta menghapus) file foto yatim.
 *
 * Foto laporan kini disimpan di disk privat (`local`), sedangkan foto
 * perumahan tetap di disk publik. Command ini memeriksa masing-masing
 * folder pada disk yang benar agar tidak salah hapus.
 */
class BersihkanFileYatim extends Command
{
    protected $signature = 'storage:bersihkan-yatim {--hapus} {--umur=24}';

    protected $description = 'Melaporkan dan, bila diminta, menghapus file foto yatim pada disk yang sesuai.';

    public function handle(): int
    {
        $umur = filter_var($this->option('umur'), FILTER_VALIDATE_INT);
        $umur = $umur !== false && $umur >= 1 ? $umur : 24;
        $sekarang = time();
        $batas = $sekarang - ($umur * 3600);

        $rujukanLaporan = $this->rujukanLaporan();
        $rujukanPerumahan = $this->rujukanPerumahan();

        $target = [
            ['disk' => LaporanFoto::DISK, 'folder' => 'laporan', 'rujukan' => $rujukanLaporan],
            ['disk' => 'public', 'folder' => 'perumahan', 'rujukan' => $rujukanPerumahan],
        ];

        $fileYatim = [];

        foreach ($target as $item) {
            $disk = Storage::disk($item['disk']);

            foreach ($disk->allFiles($item['folder']) as $path) {
                $path = $this->normalisasi($path);

                if (isset($item['rujukan'][$path])) {
                    continue;
                }

                $modified = $disk->lastModified($path);
                if ($modified >= $batas) {
                    continue;
                }

                $fileYatim[] = [
                    'disk' => $item['disk'],
                    'path' => $path,
                    'ukuran' => $disk->size($path),
                    'umur' => round(($sekarang - $modified) / 3600, 1),
                ];
            }
        }

        $this->laporkanFileYatim($fileYatim, $umur);
        $this->laporkanBerkasTakDikenal();
        $this->laporkanSisaPublik();
        $this->laporkanRujukanHilang($rujukanLaporan, $rujukanPerumahan);

        if (! $this->option('hapus')) {
            $this->info('Simulasi: tidak ada file yang dihapus. Gunakan --hapus untuk penghapusan nyata.');

            return self::SUCCESS;
        }

        $berhasil = 0;
        $gagal = 0;
        foreach ($fileYatim as $file) {
            try {
                if (! Storage::disk($file['disk'])->delete($file['path'])) {
                    $gagal++;
                    $this->error('Gagal menghapus: ['.$file['disk'].'] '.$file['path']);
                    continue;
                }

                $berhasil++;
                $this->line('Dihapus: ['.$file['disk'].'] '.$file['path']);
            } catch (\Throwable) {
                $gagal++;
                $this->error('Gagal menghapus: ['.$file['disk'].'] '.$file['path']);
            }
        }

        Log::info('storage.yatim_dihapus', ['jumlah' => $berhasil, 'gagal' => $gagal]);
        $this->info("Selesai: {$berhasil} file dihapus, {$gagal} gagal.");

        return self::SUCCESS;
    }

    /** @return array<string, true> */
    private function rujukanLaporan(): array
    {
        return LaporanFoto::query()->pluck('path')->filter()->mapWithKeys(
            fn (string $path): array => [$this->normalisasi($path) => true]
        )->all();
    }

    /** @return array<string, true> */
    private function rujukanPerumahan(): array
    {
        return Perumahan::query()->whereNotNull('foto_path')->pluck('foto_path')->filter()->mapWithKeys(
            fn (string $path): array => [$this->normalisasi($path) => true]
        )->all();
    }

    /** @param array<int, array{disk: string, path: string, ukuran: int, umur: float}> $files */
    private function laporkanFileYatim(array $files, int $umur): void
    {
        $this->line("File yatim (lebih tua dari {$umur} jam):");
        if ($files !== []) {
            $this->table(['disk', 'path', 'ukuran', 'umur (jam)'], array_map(
                fn (array $file): array => [$file['disk'], $file['path'], $file['ukuran'].' byte', $file['umur']],
                $files
            ));
        } else {
            $this->line('Tidak ada.');
        }

        $this->info('Jumlah file yatim: '.count($files).'; total ukuran: '.array_sum(array_column($files, 'ukuran')).' byte.');
    }

    private function laporkanBerkasTakDikenal(): void
    {
        $publik = collect(Storage::disk('public')->allFiles(''))
            ->map(fn (string $path): string => $this->normalisasi($path))
            ->reject(fn (string $path): bool => str_starts_with($path, 'perumahan/') || str_starts_with($path, 'laporan/'))
            ->map(fn (string $path): string => 'public:'.$path);

        $privat = collect(Storage::disk(LaporanFoto::DISK)->allFiles(''))
            ->map(fn (string $path): string => $this->normalisasi($path))
            ->reject(fn (string $path): bool => str_starts_with($path, 'laporan/'))
            ->map(fn (string $path): string => LaporanFoto::DISK.':'.$path);

        $takDikenal = $publik->merge($privat)->unique()->values()->all();

        $this->line('Berkas tak dikenal (tidak pernah dihapus):');
        foreach ($takDikenal as $path) {
            $this->line('- '.$path);
        }
        if ($takDikenal === []) {
            $this->line('Tidak ada.');
        }
    }

    /**
     * Salinan foto laporan di disk publik tidak dihapus otomatis di sini:
     * selama migrasi belum dijalankan, file itulah satu-satunya sumber foto.
     */
    private function laporkanSisaPublik(): void
    {
        $sisa = collect(Storage::disk('public')->allFiles('laporan'))
            ->map(fn (string $path): string => $this->normalisasi($path))
            ->values()
            ->all();

        $this->line('Sisa foto laporan di disk publik (tidak dihapus otomatis):');
        if ($sisa === []) {
            $this->line('Tidak ada.');
        } else {
            foreach ($sisa as $path) {
                $this->line('- '.$path);
            }
            $this->warn('Jalankan `php artisan foto:pindahkan-ke-privat` untuk memindahkannya ke disk privat.');
        }
    }

    /** @param array<string, true> $laporan @param array<string, true> $perumahan */
    private function laporkanRujukanHilang(array $laporan, array $perumahan): void
    {
        $hilang = [];
        $diskLaporan = Storage::disk(LaporanFoto::DISK);
        $diskPerumahan = Storage::disk('public');

        foreach (array_keys($laporan) as $path) {
            if (! $diskLaporan->exists($path)) {
                $hilang[] = ['sumber' => 'laporan_fotos.path ('.LaporanFoto::DISK.')', 'path' => $path];
            }
        }
        foreach (array_keys($perumahan) as $path) {
            if (! $diskPerumahan->exists($path)) {
                $hilang[] = ['sumber' => 'perumahans.foto_path (public)', 'path' => $path];
            }
        }

        $this->line('Rujukan database yang file-nya hilang:');
        if ($hilang === []) {
            $this->line('Tidak ada.');
        } else {
            $this->table(['sumber', 'path'], $hilang);
        }
        $this->info('Jumlah rujukan rusak: '.count($hilang).'.');
    }

    private function normalisasi(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
