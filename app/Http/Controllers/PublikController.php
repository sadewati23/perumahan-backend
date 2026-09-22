<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Perumahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublikController extends Controller
{
    public function beranda(): View
    {
        $statistik = [
            'total_perumahan' => Perumahan::count(),
            'total_unit' => (int) Perumahan::sum('jumlah_unit'),
            'total_luas_lahan' => (float) Perumahan::sum('luas_lahan'),
            'total_luas_rth' => (float) Perumahan::sum('luas_rth'),
            'jumlah_subsidi' => Perumahan::where('status', 'subsidi')->count(),
            'jumlah_komersil' => Perumahan::where('status', 'komersil')->count(),
            'jumlah_kecamatan' => Kecamatan::count(),
        ];

        $perKecamatan = Kecamatan::withCount('perumahans')
            ->orderByDesc('perumahans_count')
            ->orderBy('nama')
            ->take(6)
            ->get();

        $terbaru = Perumahan::with('kelurahan.kecamatan')
            ->orderByDesc('tahun_selesai')
            ->orderBy('nama')
            ->take(6)
            ->get();

        $titik = $this->titikPeta();

        return view('publik.beranda', compact('statistik', 'perKecamatan', 'terbaru', 'titik'));
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'kecamatan_id']);

        $query = Perumahan::with('kelurahan.kecamatan')->filter($filters);

        $ringkasan = [
            'jumlah' => (clone $query)->count(),
            'unit' => (int) (clone $query)->sum('jumlah_unit'),
            'luas_lahan' => (float) (clone $query)->sum('luas_lahan'),
            'luas_rth' => (float) (clone $query)->sum('luas_rth'),
        ];

        $perumahans = $query->orderBy('nama')->paginate(12)->withQueryString();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('publik.index', compact('perumahans', 'kecamatans', 'filters', 'ringkasan'));
    }

    public function show(Perumahan $perumahan): View
    {
        $perumahan->load(['kelurahan.kecamatan', 'psu', 'tipeUnits']);

        $lainnya = Perumahan::with('kelurahan')
            ->where('id', '!=', $perumahan->id)
            ->whereHas('kelurahan', fn ($q) => $q->where('kecamatan_id', $perumahan->kelurahan->kecamatan_id ?? 0))
            ->orderBy('nama')
            ->take(4)
            ->get();

        return view('publik.show', compact('perumahan', 'lainnya'));
    }

    public function peta(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'kecamatan_id']);

        $perumahans = Perumahan::with('kelurahan.kecamatan')->filter($filters)->orderBy('nama')->get();

        $titik = $perumahans->whereNotNull('latitude')->whereNotNull('longitude')
            ->map(fn (Perumahan $p) => $p->titikPeta() + ['url' => route('publik.perumahan.show', $p)])
            ->values();

        return view('publik.peta', [
            'titik' => $titik,
            'perumahans' => $perumahans,
            'kecamatans' => Kecamatan::orderBy('nama')->get(),
            'filters' => $filters,
        ]);
    }

    public function geojson(): JsonResponse
    {
        return response()->json(Perumahan::featureCollection());
    }

    private function titikPeta(): Collection
    {
        return Perumahan::with('kelurahan.kecamatan')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('nama')
            ->get()
            ->map(fn (Perumahan $p) => $p->titikPeta() + ['url' => route('publik.perumahan.show', $p)])
            ->values();
    }
}
