<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatistikService $statistik) {}

    public function index(): View
    {
        // Agregat berat (perumahan + laporan + PSU) di-cache sekali ambil;
        // detail daftar (per kecamatan, per tahun, terbaru) tetap live.
        $ringkasan = $this->statistik->ambil();

        $statistik = $ringkasan['perumahan'];
        $laporanStatistik = $ringkasan['laporan'];
        $kondisiPsu = $ringkasan['kondisi_psu'];
        $jenisPsu = $ringkasan['jenis_psu'];

        $totalPsuTerdata = (int) $kondisiPsu->sum();

        $perKecamatan = Kecamatan::query()
            ->withCount('perumahans')
            ->withSum('perumahans as total_unit', 'jumlah_unit')
            ->withSum('perumahans as total_luas_lahan', 'luas_lahan')
            ->orderByDesc('perumahans_count')
            ->take(8)
            ->get();

        $perTahun = Perumahan::query()
            ->whereNotNull('tahun_selesai')
            ->select('tahun_selesai', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(jumlah_unit) as unit'))
            ->groupBy('tahun_selesai')
            ->orderBy('tahun_selesai')
            ->take(10)
            ->get();

        $terbaru = Perumahan::with('kelurahan.kecamatan')->latest()->take(5)->get();

        $laporanTerbaru = Laporan::with(['perumahan', 'kelurahan.kecamatan'])->latest()->take(5)->get();

        return view('dashboard', compact(
            'statistik',
            'perKecamatan',
            'perTahun',
            'kondisiPsu',
            'totalPsuTerdata',
            'jenisPsu',
            'terbaru',
            'laporanStatistik',
            'laporanTerbaru',
        ));
    }
}
