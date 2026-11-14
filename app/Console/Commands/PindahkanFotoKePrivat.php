<?php

namespace App\Console\Commands;

use App\Models\LaporanFoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Memindahkan foto laporan lama dari disk publik ke disk privat.
 *
 * Sebelumnya foto laporan disimpan di storage/app/public sehingga dapat
 * dibuka siapa pun yang mengetahui URL-nya. Command ini menyalin file ke
 * disk `local` (storage/app/private), menghapus salinan publik, dan
 * menormalkan path di database bila perlu.
 *
 * Mode default adalah simulasi (tidak mengubah apa pun).
 */
class PindahkanFotoKePrivat extends Command
{
    protected $signature = 'foto:pindahkan-ke-privat
                            {--jalankan : Eksekusi pemindahan (tanpa opsi ini hanya simulasi)}';

    protected $description = 'Memindahkan foto laporan dari disk publik ke disk privat (default: simulasi).';

    public function handle(): int
    {
        $publik = Storage::disk('public');
        $privat = Storage::disk(LaporanFoto::DISK);
        $jalankan = (bool) $this->option('jalankan');

        $dipindah = 0;
        $sudahPrivat = 0;
        $duplikat = 0;
        $hilang = 0;
        $gagal = 0;
        $baris = [];

        LaporanFoto::query()->orderBy('id')->chunkById(200, function ($fotos) use (
            $publik,
            $privat,
            $jalankan,
            &$dipindah,
            &$sudahPrivat,
            &$duplikat,
            &$hilang,
            &$gagal,
            &$baris,
        ): void {
            foreach ($fotos as $foto) {
                $path = $this->normalisasi((string) $foto->path);

                if ($path === '') {
                    $hilang++;
                    $baris[] = [$foto->id, '(kosong)', 'path kosong — dilewati'];

                    continue;
                }

                $adaPrivat = $privat->exists($path);
                $adaPublik = $publik->exists($path);

                if ($adaPrivat) {
                    $sudahPrivat++;

                    // Salinan publik yang tertinggal tetap harus dihapus: selama
                    // ada, file masih bisa diakses tanpa login.
                    if ($adaPublik) {
                        $duplikat++;
                        if ($jalankan) {
                            $publik->delete($path);
                        }
                        $baris[] = [$foto->id, $path, $jalankan ? 'duplikat publik dihapus' : 'duplikat publik akan dihapus'];
                    }

                    if ($jalankan && $foto->path !== $path) {
                        $foto->update(['path' => $path]);
                    }

                    continue;
                }

                if (! $adaPublik) {
                    $hilang++;
                    $baris[] = [$foto->id, $path, 'file tidak ditemukan di kedua disk'];

                    continue;
                }

                if (! $jalankan) {
                    $dipindah++;
                    $baris[] = [$foto->id, $path, 'akan dipindah ke privat'];

                    continue;
                }

                try {
                    $stream = $publik->readStream($path);
                    if (! is_resource($stream)) {
                        throw new \RuntimeException('Gagal membaca file publik.');
                    }

                    $berhasilTulis = $privat->writeStream($path, $stream);
                    fclose($stream);

                    if (! $berhasilTulis) {
                        throw new \RuntimeException('Gagal menulis file privat.');
                    }

                    if (! $publik->delete($path)) {
                        // Data sudah aman di privat, tetapi salinan publik masih ada.
                        Log::warning('laporan.foto_publik_gagal_dihapus', ['path' => $path]);
                    }

                    if ($foto->path !== $path) {
                        $foto->update(['path' => $path]);
                    }

                    $dipindah++;
                    $baris[] = [$foto->id, $path, 'dipindah'];
                } catch (Throwable $e) {
                    $gagal++;
                    Log::error('laporan.foto_gagal_dipindah', ['id' => $foto->id, 'path' => $path]);
                    $baris[] = [$foto->id, $path, 'GAGAL: '.$e->getMessage()];
                }
            }
        });

        if ($baris !== []) {
            $this->table(['id', 'path', 'status'], $baris);
        }

        $this->info(sprintf(
            '%s: %d dipindah, %d sudah privat, %d duplikat publik, %d hilang, %d gagal.',
            $jalankan ? 'Selesai' : 'Simulasi',
            $dipindah,
            $sudahPrivat,
            $duplikat,
            $hilang,
            $gagal,
        ));

        if (! $jalankan) {
            $this->line('Tidak ada file yang diubah. Jalankan ulang dengan --jalankan untuk memindahkan.');
        }

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function normalisasi(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
