<?php

namespace App\Services;

use App\Models\Perumahan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Sumber data tunggal untuk titik peta, GeoJSON, dan ringkasan perumahan.
 * Dipakai bersama oleh PublikController dan PerumahanController agar logika
 * tidak terduplikasi dan cache antar halaman konsisten.
 */
class PerumahanPetaService
{
    public const CACHE_PERUMAHAN = 'peta.perumahan';

    public const CACHE_GEOJSON = 'peta.geojson';

    /**
     * Semua perumahan beserta wilayahnya (dipakai peta admin, beranda, dan peta publik tanpa filter).
     * Di-cache beberapa menit karena tabel ini jarang berubah dan bisa memuat banyak baris.
     */
    public function perumahanDenganWilayah(): Collection
    {
        return Cache::remember(
            self::CACHE_PERUMAHAN,
            now()->addMinutes(5),
            fn () => Perumahan::query()
                ->with('kelurahan.kecamatan')
                ->orderBy('nama')
                ->get()
        );
    }

    /**
     * Titik peta dari kumpulan perumahan, dengan URL detail sesuai route tujuan.
     *
     * @param  Collection<int, Perumahan>  $perumahans
     */
    public function titik(Collection $perumahans, string $routeName): Collection
    {
        return $perumahans
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->map(fn (Perumahan $p) => $p->titikPeta() + ['url' => route($routeName, $p)])
            ->values();
    }

    /**
     * GeoJSON FeatureCollection seluruh perumahan berkoordinat (di-cache).
     */
    public function featureCollection(): array
    {
        return Cache::remember(
            self::CACHE_GEOJSON,
            now()->addMinutes(5),
            fn () => Perumahan::featureCollection()
        );
    }

    /**
     * Ringkasan angka (jumlah, unit, luas) dari query yang sudah difilter.
     */
    public function ringkasan(Builder $query): array
    {
        return [
            'jumlah' => (clone $query)->count(),
            'unit' => (int) (clone $query)->sum('jumlah_unit'),
            'luas_lahan' => (float) (clone $query)->sum('luas_lahan'),
            'luas_rth' => (float) (clone $query)->sum('luas_rth'),
        ];
    }

    /**
     * Hapus cache peta/geojson — panggil setelah data perumahan berubah.
     */
    public function hapusCache(): void
    {
        Cache::forget(self::CACHE_PERUMAHAN);
        Cache::forget(self::CACHE_GEOJSON);
    }
}
