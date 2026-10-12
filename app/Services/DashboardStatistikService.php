<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\PsuPerumahan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Statistik agregat dashboard dalam sedikit query (agregasi kondisional)
 * dan di-cache beberapa menit agar dashboard murah saat sering dibuka.
 */
class DashboardStatistikService
{
    public const CACHE_KEY = 'dashboard.statistik';

    /**
     * @return array{perumahan: array, laporan: array, kondisi_psu: Collection, jenis_psu: Collection}
     */
    public function ambil(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            return [
                // total_psu & psu_rusak digabung ke array perumahan agar kontrak
                // variabel $statistik di view dashboard tetap sama.
                'perumahan' => $this->statistikPerumahan() + $this->statistikPsu(),
                'laporan' => $this->statistikLaporan(),
                'kondisi_psu' => $this->kondisiPsu(),
                'jenis_psu' => $this->jenisPsu(),
            ];
        });
    }

    /**
     * Dua agregat PSU dalam satu query.
     *
     * @return array{total_psu: int, psu_rusak: int}
     */
    private function statistikPsu(): array
    {
        $baris = PsuPerumahan::query()
            ->selectRaw("
                COUNT(*) as total_psu,
                COALESCE(SUM(CASE WHEN kondisi IN ('rusak_ringan', 'rusak_berat') THEN 1 ELSE 0 END), 0) as psu_rusak
            ")
            ->first();

        return [
            'total_psu' => (int) $baris->total_psu,
            'psu_rusak' => (int) $baris->psu_rusak,
        ];
    }

    /**
     * Satu query menggantikan belasan count()/sum() terpisah (agregasi kondisional).
     *
     * @return array{total_perumahan: int, total_unit: int, total_luas_lahan: float, total_luas_rth: float, jumlah_subsidi: int, jumlah_komersil: int, jumlah_pemakaman: int, belum_koordinat: int}
     */
    private function statistikPerumahan(): array
    {
        $baris = Perumahan::query()
            ->selectRaw("
                COUNT(*) as total_perumahan,
                COALESCE(SUM(jumlah_unit), 0) as total_unit,
                COALESCE(SUM(luas_lahan), 0) as total_luas_lahan,
                COALESCE(SUM(luas_rth), 0) as total_luas_rth,
                COALESCE(SUM(CASE WHEN status = 'subsidi' THEN 1 ELSE 0 END), 0) as jumlah_subsidi,
                COALESCE(SUM(CASE WHEN status = 'komersil' THEN 1 ELSE 0 END), 0) as jumlah_komersil,
                COALESCE(SUM(CASE WHEN ada_lahan_pemakaman = 1 THEN 1 ELSE 0 END), 0) as jumlah_pemakaman,
                COALESCE(SUM(CASE WHEN latitude IS NULL OR longitude IS NULL THEN 1 ELSE 0 END), 0) as belum_koordinat
            ")
            ->first();

        return [
            'total_perumahan' => (int) $baris->total_perumahan,
            'total_unit' => (int) $baris->total_unit,
            'total_luas_lahan' => (float) $baris->total_luas_lahan,
            'total_luas_rth' => (float) $baris->total_luas_rth,
            'jumlah_subsidi' => (int) $baris->jumlah_subsidi,
            'jumlah_komersil' => (int) $baris->jumlah_komersil,
            'jumlah_pemakaman' => (int) $baris->jumlah_pemakaman,
            'belum_koordinat' => (int) $baris->belum_koordinat,
        ];
    }

    /**
     * Satu query untuk seluruh statistik laporan warga: total, per status, dan per jenis.
     *
     * @return array{total: int, baru: int, diproses: int, selesai: int, kerusakan: int, pengaduan: int, bangunan_liar: int}
     */
    private function statistikLaporan(): array
    {
        $baris = Laporan::query()
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END), 0) as baru,
                COALESCE(SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END), 0) as diproses,
                COALESCE(SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END), 0) as selesai,
                COALESCE(SUM(CASE WHEN jenis = 'kerusakan' THEN 1 ELSE 0 END), 0) as kerusakan,
                COALESCE(SUM(CASE WHEN jenis = 'pengaduan' THEN 1 ELSE 0 END), 0) as pengaduan,
                COALESCE(SUM(CASE WHEN jenis = 'bangunan_liar' THEN 1 ELSE 0 END), 0) as bangunan_liar
            ")
            ->first();

        return [
            'total' => (int) $baris->total,
            'baru' => (int) $baris->baru,
            'diproses' => (int) $baris->diproses,
            'selesai' => (int) $baris->selesai,
            'kerusakan' => (int) $baris->kerusakan,
            'pengaduan' => (int) $baris->pengaduan,
            'bangunan_liar' => (int) $baris->bangunan_liar,
        ];
    }

    /**
     * Komposisi kondisi PSU untuk grafik donat (sudah groupBy).
     */
    private function kondisiPsu(): Collection
    {
        return PsuPerumahan::query()
            ->select('kondisi', DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('kondisi')
            ->groupBy('kondisi')
            ->pluck('jumlah', 'kondisi');
    }

    /**
     * Jenis PSU terbanyak (top 6) untuk grafik bar horizontal (sudah groupBy).
     */
    private function jenisPsu(): Collection
    {
        return PsuPerumahan::query()
            ->select('jenis', DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('jenis')
            ->groupBy('jenis')
            ->orderByDesc('jumlah')
            ->take(6)
            ->pluck('jumlah', 'jenis');
    }
}
