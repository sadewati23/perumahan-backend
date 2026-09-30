<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Perumahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerumahanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'kecamatan_id', 'tahun']);

        $query = Perumahan::with('kelurahan.kecamatan')->filter($filters);

        $ringkasan = [
            'jumlah' => (clone $query)->count(),
            'unit' => (int) (clone $query)->sum('jumlah_unit'),
            'luas_lahan' => (float) (clone $query)->sum('luas_lahan'),
            'luas_rth' => (float) (clone $query)->sum('luas_rth'),
        ];

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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $perumahan = Perumahan::create($data);
        $this->syncDetail($perumahan, $request);

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

    public function update(Request $request, Perumahan $perumahan): RedirectResponse
    {
        $data = $this->validated($request);

        $perumahan->update($data);
        $this->syncDetail($perumahan, $request);

        return redirect()->route('perumahan.show', $perumahan)
            ->with('success', 'Data perumahan "'.$perumahan->nama.'" berhasil diperbarui.');
    }

    public function destroy(Perumahan $perumahan): RedirectResponse
    {
        $nama = $perumahan->nama;

        // Hard delete permanen: baris induk dihapus, psu_perumahans &
        // tipe_units ikut terhapus lewat foreign key cascadeOnDelete().
        $perumahan->delete();

        return redirect()->route('perumahan.index')
            ->with('success', 'Data perumahan "'.$nama.'" beserta seluruh detailnya telah dihapus permanen.');
    }

    /**
     * Halaman peta sebaran perumahan (titik GIS).
     */
    public function peta(Request $request): View
    {
        $perumahans = Perumahan::with('kelurahan.kecamatan')->orderBy('nama')->get();

        $titik = $perumahans->whereNotNull('latitude')->whereNotNull('longitude')
            ->map(fn (Perumahan $p) => $p->titikPeta() + ['url' => route('perumahan.show', $p)])
            ->values();

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
        return response()->json(Perumahan::featureCollection());
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'ada_lahan_pemakaman' => $request->boolean('ada_lahan_pemakaman'),
        ]);

        $tahunSekarang = (int) date('Y');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'pengembang' => ['nullable', 'string', 'max:150'],
            'kelurahan_id' => ['required', 'exists:kelurahans,id'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'luas_lahan' => ['required', 'numeric', 'min:0'],
            'jumlah_unit' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:subsidi,komersil'],
            'tahun_pembuatan' => ['nullable', 'integer', 'min:1900', 'max:'.($tahunSekarang + 1)],
            'tahun_selesai' => ['nullable', 'integer', 'min:1900', 'max:'.($tahunSekarang + 10)],
            'jumlah_rth' => ['nullable', 'integer', 'min:0'],
            'luas_rth' => ['nullable', 'numeric', 'min:0'],
            'kdb' => ['nullable', 'numeric', 'between:0,100'],
            'kdh' => ['nullable', 'numeric', 'between:0,100'],
            'ada_lahan_pemakaman' => ['boolean'],
            'luas_lahan_pemakaman' => ['nullable', 'numeric', 'min:0'],
            'lokasi_lahan_pemakaman' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'psu' => ['nullable', 'array'],
            'psu.*.jenis' => ['nullable', 'string', 'max:100'],
            'psu.*.jumlah' => ['nullable', 'numeric', 'min:0'],
            'psu.*.satuan' => ['nullable', 'string', 'max:30'],
            'psu.*.kondisi' => ['nullable', 'in:baik,rusak_ringan,rusak_berat'],
            'psu.*.keterangan' => ['nullable', 'string', 'max:255'],
            'tipe_units' => ['nullable', 'array'],
            'tipe_units.*.tipe' => ['nullable', 'string', 'max:50'],
            'tipe_units.*.jumlah_unit' => ['nullable', 'integer', 'min:0'],
            'tipe_units.*.luas_tanah' => ['nullable', 'numeric', 'min:0'],
            'tipe_units.*.luas_bangunan' => ['nullable', 'numeric', 'min:0'],

            'tipe_units.*.keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama perumahan wajib diisi.',
            'kelurahan_id.required' => 'Kelurahan wajib dipilih.',
            'luas_lahan.required' => 'Luas lahan wajib diisi.',
            'jumlah_unit.required' => 'Jumlah unit wajib diisi.',
            'status.required' => 'Status perumahan wajib dipilih.',
        ]);

        if (! empty($data['tahun_pembuatan']) && ! empty($data['tahun_selesai'])
            && $data['tahun_selesai'] < $data['tahun_pembuatan']) {
            throw ValidationException::withMessages([
                'tahun_selesai' => 'Tahun selesai tidak boleh lebih awal dari tahun pembuatan.',
            ]);
        }

        if (! $data['ada_lahan_pemakaman']) {
            $data['luas_lahan_pemakaman'] = null;
            $data['lokasi_lahan_pemakaman'] = null;
        }

        return $data;
    }

    private function syncDetail(Perumahan $perumahan, Request $request): void
    {
        $perumahan->psu()->delete();
        foreach ((array) $request->input('psu', []) as $row) {
            if (blank($row['jenis'] ?? null)) {
                continue;
            }

            $perumahan->psu()->create([
                'jenis' => $row['jenis'],
                'jumlah' => $row['jumlah'] ?? 0,
                'satuan' => $row['satuan'] ?? null,
                'kondisi' => $row['kondisi'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
            ]);
        }

        $perumahan->tipeUnits()->delete();
        foreach ((array) $request->input('tipe_units', []) as $row) {
            if (blank($row['tipe'] ?? null)) {
                continue;
            }

            $perumahan->tipeUnits()->create([
                'tipe' => $row['tipe'],
                'jumlah_unit' => $row['jumlah_unit'] ?? 0,
                'luas_tanah' => $row['luas_tanah'] ?? null,
                'luas_bangunan' => $row['luas_bangunan'] ?? null,
                'keterangan' => $row['keterangan'] ?? null,
            ]);
        }
    }
}
