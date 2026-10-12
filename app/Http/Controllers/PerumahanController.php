<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerumahanRequest;
use App\Models\Kecamatan;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use App\Services\PerumahanPetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PerumahanController extends Controller
{
    public function __construct(private readonly PerumahanPetaService $peta) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'kecamatan_id', 'tahun']);

        $query = Perumahan::with('kelurahan.kecamatan')->filter($filters);

        $ringkasan = $this->peta->ringkasan($query);

        $perumahans = $query->orderBy('nama')->paginate(10)->withQueryString();

        $kecamatans = Kecamatan::orderBy('nama')->get();
        $tahunList = Perumahan::query()
            ->whereNotNull('tahun_selesai')
            ->distinct()
            ->orderByDesc('tahun_selesai')
            ->pluck('tahun_selesai');

        return view('perumahan.index', compact('perumahans', 'kecamatans', 'tahunList', 'filters', 'ringkasan'));
    }

    public function create(): View
    {
        return view('perumahan.create', [
            'perumahan' => new Perumahan(['status' => 'komersil', 'luas_lahan' => 0, 'jumlah_unit' => 0]),
            'kecamatans' => Kecamatan::with('kelurahans')->orderBy('nama')->get(),
        ]);
    }

    public function store(StorePerumahanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $perumahan = DB::transaction(function () use ($data, $request): Perumahan {
            $perumahan = Perumahan::create($data);
            $this->syncDetail($perumahan, $request);

            return $perumahan;
        });

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.show', $perumahan)
            ->with('success', 'Data perumahan "'.$perumahan->nama.'" berhasil disimpan.');
    }

    public function show(Perumahan $perumahan): View
    {
        $perumahan->load(['kelurahan.kecamatan', 'psu', 'tipeUnits']);

        return view('perumahan.show', compact('perumahan'));
    }

    public function edit(Perumahan $perumahan): View
    {
        $perumahan->load(['psu', 'tipeUnits']);

        return view('perumahan.edit', [
            'perumahan' => $perumahan,
            'kecamatans' => Kecamatan::with('kelurahans')->orderBy('nama')->get(),
        ]);
    }

    public function update(StorePerumahanRequest $request, Perumahan $perumahan): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $perumahan, $request): void {
            $perumahan->update($data);
            $this->syncDetail($perumahan, $request);
        });

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.show', $perumahan)
            ->with('success', 'Data perumahan "'.$perumahan->nama.'" berhasil diperbarui.');
    }

    public function destroy(Perumahan $perumahan): RedirectResponse
    {
        $nama = $perumahan->nama;

        // Hard delete permanen: baris induk dihapus, psu_perumahans &
        // tipe_units ikut terhapus lewat foreign key cascadeOnDelete().
        $perumahan->delete();

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.index')
            ->with('success', 'Data perumahan "'.$nama.'" beserta seluruh detailnya telah dihapus permanen.');
    }

    /**
     * Halaman peta sebaran perumahan (titik GIS).
     */
    public function peta(Request $request): View
    {
        $perumahans = $this->peta->perumahanDenganWilayah();

        $titik = $this->peta->titik($perumahans, 'perumahan.show');

        // Kelompokkan koordinat titik lokasi per kecamatan agar peta bisa
        // fokus ke satu kecamatan saat chip-nya diklik.
        $kecamatanFokus = [];
        foreach ($perumahans as $p) {
            $kecamatan = $p->kelurahan?->kecamatan;
            if (! $kecamatan || ! $p->punya_koordinat) {
                continue;
            }

            if (! isset($kecamatanFokus[$kecamatan->id])) {
                $kecamatanFokus[$kecamatan->id] = [
                    'id' => $kecamatan->id,
                    'nama' => $kecamatan->nama,
                    'koordinat' => [],
                ];
            }

            $kecamatanFokus[$kecamatan->id]['koordinat'][] = [(float) $p->latitude, (float) $p->longitude];
        }

        $kecamatanFokus = array_values($kecamatanFokus);

        return view('perumahan.peta', [
            'titik' => $titik,
            'perumahans' => $perumahans,
            'kecamatanFokus' => $kecamatanFokus,
            'fokusAktif' => (int) $request->query('kecamatan', 0) ?: null,
        ]);
    }

    /**
     * Titik perumahan dalam format GeoJSON (dipakai peta & integrasi lain).
     */
    public function geojson(): JsonResponse
    {
        return response()->json($this->peta->featureCollection());
    }

    /**
     * Sinkronkan baris PSU & tipe unit dari form. Baris lama yang masih ada
     * di form (dikenali dari hidden input id) di-update tanpa mengganti id;
     * hanya baris yang memang dihapus dari form yang di-delete.
     *
     * PENTING: jangan delete-all + create ulang — FK laporans.psu_perumahan_id
     * memakai nullOnDelete, jadi delete-all akan memutus relasi laporan -> PSU
     * setiap kali data perumahan disimpan.
     */
    private function syncDetail(Perumahan $perumahan, Request $request): void
    {
        $this->syncPsu($perumahan, (array) $request->input('psu', []));
        $this->syncTipeUnits($perumahan, (array) $request->input('tipe_units', []));
    }

    private function syncPsu(Perumahan $perumahan, array $rows): void
    {
        $idPertahankan = [];

        foreach ($rows as $row) {
            if (blank($row['jenis'] ?? null)) {
                continue;
            }

            $data = [
                'jenis' => $row['jenis'],
                'jumlah' => $row['jumlah'] ?? 0,
                'satuan' => $row['satuan'] ?? null,
                'kondisi' => $row['kondisi'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
            ];

            $psu = $perumahan->psu()->find((int) ($row['id'] ?? 0));

            if ($psu) {
                $psu->update($data);
                $idPertahankan[] = $psu->id;
            } else {
                $idPertahankan[] = $perumahan->psu()->create($data)->id;
            }
        }

        $perumahan->psu()->whereNotIn('id', $idPertahankan)->delete();
    }

    private function syncTipeUnits(Perumahan $perumahan, array $rows): void
    {
        $idPertahankan = [];

        foreach ($rows as $row) {
            if (blank($row['tipe'] ?? null)) {
                continue;
            }

            $data = [
                'tipe' => $row['tipe'],
                'jumlah_unit' => $row['jumlah_unit'] ?? 0,
                'luas_tanah' => $row['luas_tanah'] ?? null,
                'luas_bangunan' => $row['luas_bangunan'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
            ];

            $tipeUnit = $perumahan->tipeUnits()->find((int) ($row['id'] ?? 0));

            if ($tipeUnit) {
                $tipeUnit->update($data);
                $idPertahankan[] = $tipeUnit->id;
            } else {
                $idPertahankan[] = $perumahan->tipeUnits()->create($data)->id;
            }
        }

        $perumahan->tipeUnits()->whereNotIn('id', $idPertahankan)->delete();
    }
}
