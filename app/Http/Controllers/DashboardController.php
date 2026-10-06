<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\PsuPerumahan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statistik = [
            'total_perumahan' => Perumahan::count(),
            'total_unit' => (int) Perumahan::sum('jumlah_unit'),
            'total_luas_lahan' => (float) Perumahan::sum('luas_lahan'),
            'total_luas_rth' => (float) Perumahan::sum('luas_rth'),
            'jumlah_subsidi' => Perumahan::where('status', 'subsidi')->count(),
            'jumlah_komersil' => Perumahan::where('status', 'komersil')->count(),
            'jumlah_pemakaman' => Perumahan::where('ada_lahan_pemakaman', true)->count(),
            'belum_koordinat' => Perumahan::whereNull('latitude')->orWhereNull('longitude')->count(),
            'total_psu' => PsuPerumahan::count(),
            'psu_rusak' => PsuPerumahan::whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count(),
        ];

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

        // Komposisi kondisi PSU untuk grafik donat.
        $kondisiPsu = PsuPerumahan::query()
            ->select('kondisi', DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('kondisi')
            ->groupBy('kondisi')
            ->pluck('jumlah', 'kondisi');

        $totalPsuTerdata = (int) $kondisiPsu->sum();

        // Jenis PSU terbanyak (top 6) untuk grafik bar horizontal.
        $jenisPsu = PsuPerumahan::query()
            ->select('jenis', DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('jenis')
            ->groupBy('jenis')
            ->orderByDesc('jumlah')
            ->take(6)
            ->pluck('jumlah', 'jenis');

        $terbaru = Perumahan::with('kelurahan.kecamatan')->latest()->take(5)->get();

        // Ringkasan laporan warga (semua jenis: kerusakan, pengaduan, bangunan liar).
        $laporanStatistik = [
            'total' => Laporan::count(),
            'baru' => Laporan::where('status', 'baru')->count(),
            'diproses' => Laporan::where('status', 'diproses')->count(),
            'selesai' => Laporan::where('status', 'selesai')->count(),
            'kerusakan' => Laporan::where('jenis', Laporan::JENIS_KERUSAKAN)->count(),
            'pengaduan' => Laporan::where('jenis', Laporan::JENIS_PENGADUAN)->count(),
            'bangunan_liar' => Laporan::where('jenis', Laporan::JENIS_BANGUNAN_LIAR)->count(),
        ];

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
