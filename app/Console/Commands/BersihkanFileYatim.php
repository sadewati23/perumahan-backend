<?php

namespace App\Console\Commands;

use App\Models\LaporanFoto;
use App\Models\Perumahan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BersihkanFileYatim extends Command
{
    protected $signature = 'storage:bersihkan-yatim {--hapus} {--umur=24}';

    protected $description = 'Melaporkan dan, bila diminta, menghapus file foto yatim pada disk public.';

    public function handle(): int
    {
        $umur = filter_var($this->option('umur'), FILTER_VALIDATE_INT);
        $umur = $umur !== false && $umur >= 1 ? $umur : 24;
        $disk = Storage::disk('public');
        $sekarang = time();
        $batas = $sekarang - ($umur * 3600);

        $rujukanLaporan = $this->rujukanLaporan();
        $rujukanPerumahan = $this->rujukanPerumahan();
        $fileYatim = [];

        foreach ([
            'laporan' => $rujukanLaporan,
            'perumahan' => $rujukanPerumahan,
        ] as $folder => $rujukan) {
            foreach ($disk->allFiles($folder) as $path) {
                $path = $this->normalisasi($path);

                if (isset($rujukan[$path])) {
                    continue;
                }

                $modified = $disk->lastModified($path);
                if ($modified >= $batas) {
                    continue;
                }

                $fileYatim[] = [
                    'path' => $path,
                    'ukuran' => $disk->size($path),
                    'umur' => round(($sekarang - $modified) / 3600, 1),
                ];
            }
        }

        $this->laporkanFileYatim($fileYatim, $umur);
        $this->laporkanBerkasTakDikenal($disk);
        $this->laporkanRujukanHilang($disk, $rujukanLaporan, $rujukanPerumahan);

        if (! $this->option('hapus')) {
            $this->info('Simulasi: tidak ada file yang dihapus. Gunakan --hapus untuk penghapusan nyata.');

            return self::SUCCESS;
        }

        $berhasil = 0;
        $gagal = 0;
        foreach ($fileYatim as $file) {
            try {
                if (! $disk->delete($file['path'])) {
                    $gagal++;
                    $this->error('Gagal menghapus: '.$file['path']);
                    continue;
                }

                $berhasil++;
                $this->line('Dihapus: '.$file['path']);
            } catch (\Throwable) {
                $gagal++;
                $this->error('Gagal menghapus: '.$file['path']);
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

    /** @param array<int, array{path: string, ukuran: int, umur: float}> $files */
    private function laporkanFileYatim(array $files, int $umur): void
    {
        $this->line("File yatim (lebih tua dari {$umur} jam):");
        if ($files !== []) {
            $this->table(['path', 'ukuran', 'umur (jam)'], array_map(
                fn (array $file): array => [$file['path'], $file['ukuran'].' byte', $file['umur']],
                $files
            ));
        } else {
            $this->line('Tidak ada.');
        }

        $this->info('Jumlah file yatim: '.count($files).'; total ukuran: '.array_sum(array_column($files, 'ukuran')).' byte.');
    }

    private function laporkanBerkasTakDikenal($disk): void
    {
        $takDikenal = collect($disk->allFiles(''))->map(fn (string $path): string => $this->normalisasi($path))
            ->reject(fn (string $path): bool => str_starts_with($path, 'laporan/') || str_starts_with($path, 'perumahan/'))
            ->values()->all();

        $this->line('Berkas tak dikenal (tidak pernah dihapus):');
        foreach ($takDikenal as $path) {
            $this->line('- '.$path);
        }
        if ($takDikenal === []) {
            $this->line('Tidak ada.');
        }
    }

    /** @param array<string, true> $laporan @param array<string, true> $perumahan */
    private function laporkanRujukanHilang($disk, array $laporan, array $perumahan): void
    {
        $hilang = [];
        foreach (array_keys($laporan) as $path) {
            if (! $disk->exists($path)) {
                $hilang[] = ['sumber' => 'laporan_fotos.path', 'path' => $path];
            }
        }
        foreach (array_keys($perumahan) as $path) {
            if (! $disk->exists($path)) {
                $hilang[] = ['sumber' => 'perumahans.foto_path', 'path' => $path];
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
