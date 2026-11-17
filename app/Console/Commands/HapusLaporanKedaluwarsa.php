<?php

namespace App\Console\Commands;

use App\Models\Laporan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HapusLaporanKedaluwarsa extends Command
{
    protected $signature = 'laporan:hapus-kedaluwarsa {--dry-run} {--maks=500}';
    protected $description = 'Menghapus permanen laporan selesai yang sudah kedaluwarsa.';

    public function handle(): int
    {
        if (! config('laporan.hapus_otomatis', true)) {
            $this->info('Penghapusan otomatis laporan nonaktif.'); return self::SUCCESS;
        }
        $tanpaBatasLama = blank(config('laporan.hapus_otomatis_sejak'));
        if ($tanpaBatasLama) {
            if (! $this->option('dry-run')) {
                $this->error('Penghapusan otomatis dibatalkan: LAPORAN_HAPUS_OTOMATIS_SEJAK belum diisi. Isi tanggal deploy (YYYY-MM-DD) pada konfigurasi agar laporan lama tidak ikut terhapus. Tidak ada laporan yang dihapus.');
                Log::error('laporan.hapus_otomatis_tanpa_batas_lama', ['aksi' => 'dibatalkan']);

                return self::FAILURE;
            }
            $this->warn('LAPORAN_HAPUS_OTOMATIS_SEJAK kosong: laporan lama yang sudah selesai ikut terdaftar. Mode dry-run tidak menghapus data.');
        }
        $maks = max(1, (int) $this->option('maks'));
        $laporans = Laporan::jatuhTempoHapus()->orderBy('id')->limit($maks)->get();
        if ($this->option('dry-run')) {
            $this->table(['kode', 'jenis', 'selesai_pada', 'jatuh_tempo_hapus'], $laporans->map(fn ($l) => [$l->kode, $l->jenis, $l->selesai_pada, $l->jatuh_tempo_hapus])->all());
            $this->info('Dry-run: '.$laporans->count().' laporan akan dihapus.'); return self::SUCCESS;
        }
        $kode = [];
        foreach ($laporans as $laporan) {
            try { $kode[] = $laporan->kode; $laporan->hapusPermanen(); }
            catch (\Throwable $e) { Log::error('laporan.gagal_dihapus', ['kode' => $laporan->kode]); $this->error('Gagal menghapus '.$laporan->kode); }
        }
        Log::info('laporan.dihapus_otomatis', ['jumlah' => count($kode), 'kode' => $kode]);
        $this->info('Selesai: '.count($kode).' laporan dihapus.'); return self::SUCCESS;
    }
}
