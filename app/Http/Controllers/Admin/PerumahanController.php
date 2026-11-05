<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePerumahanRequest;
use App\Models\Kecamatan;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use App\Services\PerumahanPetaService;
use App\Support\FilterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class PerumahanController extends Controller
{
    public function __construct(private readonly PerumahanPetaService $peta) {}

    public function index(Request $request): View
    {
        $filters = FilterQuery::ambil($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(Perumahan::STATUS))],
            'kecamatan_id' => ['nullable', 'integer', 'min:1'],
            'tahun' => ['nullable', 'integer', 'between:1900,2200'],
        ]);

        $query = Perumahan::with('kelurahan.kecamatan')->filter($filters);

        $ringkasan = $this->peta->ringkasan($query);

        $perumahans = $query->orderBy('nama')->paginate(10)->appends($filters);

        $kecamatans = Kecamatan::orderBy('nama')->get();
        $tahunList = Perumahan::query()
            ->whereNotNull('tahun_selesai')
            ->distinct()
            ->orderByDesc('tahun_selesai')
            ->pluck('tahun_selesai');

        return view('admin.perumahan.index', compact('perumahans', 'kecamatans', 'tahunList', 'filters', 'ringkasan'));
    }

    public function create(): View
    {
        return view('admin.perumahan.create', [
            'perumahan' => new Perumahan(['status' => 'komersil', 'luas_lahan' => 0, 'jumlah_unit' => 0]),
            'kecamatans' => Kecamatan::with('kelurahans')->orderBy('nama')->get(),
        ]);
    }

    public function store(StorePerumahanRequest $request): RedirectResponse
    {
        $this->pastikanFotoValid($request);
        $data = $request->validated();
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            try {
                $fotoPath = \App\Support\FotoBersih::simpan($request->file('foto'), 'perumahan');
            } catch (\RuntimeException) {
                throw ValidationException::withMessages([
                    'foto' => 'Foto tidak dapat diproses. Gunakan file JPG, PNG, atau WEBP yang valid.',
                ]);
            }
        }
        unset($data['foto']);
        if ($fotoPath) {
            $data['foto_path'] = $fotoPath;
        }

        try {
            $perumahan = DB::transaction(function () use ($data, $request): Perumahan {
                $perumahan = Perumahan::create($data);
                $this->syncDetail($perumahan, $request);

                return $perumahan;
            });
        } catch (\Throwable $exception) {
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.show', $perumahan)
            ->with('success', 'Data perumahan "'.$perumahan->nama.'" berhasil disimpan.');
    }

    public function show(Perumahan $perumahan): View
    {
        $perumahan->load(['kelurahan.kecamatan', 'psu', 'tipeUnits']);

        return view('admin.perumahan.show', compact('perumahan'));
    }

    public function edit(Perumahan $perumahan): View
    {
        $perumahan->load(['psu', 'tipeUnits']);

        return view('admin.perumahan.edit', [
            'perumahan' => $perumahan,
            'kecamatans' => Kecamatan::with('kelurahans')->orderBy('nama')->get(),
        ]);
    }

    public function update(StorePerumahanRequest $request, Perumahan $perumahan): RedirectResponse
    {
        $this->pastikanFotoValid($request);
        $data = $request->validated();
        $fotoLama = $perumahan->foto_path;
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            try {
                $fotoPath = \App\Support\FotoBersih::simpan($request->file('foto'), 'perumahan');
            } catch (\RuntimeException) {
                throw ValidationException::withMessages([
                    'foto' => 'Foto tidak dapat diproses. Gunakan file JPG, PNG, atau WEBP yang valid.',
                ]);
            }
        }
        unset($data['foto']);
        if ($fotoPath) {
            $data['foto_path'] = $fotoPath;
        }

        try {
            DB::transaction(function () use ($data, $perumahan, $request): void {
                $perumahan->update($data);
                $this->syncDetail($perumahan, $request);
            });
        } catch (\Throwable $exception) {
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }
        if ($fotoPath && $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.show', $perumahan)
            ->with('success', 'Data perumahan "'.$perumahan->nama.'" berhasil diperbarui.');
    }

    public function destroy(Perumahan $perumahan): RedirectResponse
    {
        $nama = $perumahan->nama;
        $paths = LaporanFoto::whereIn(
            'laporan_id',
            Laporan::where('perumahan_id', $perumahan->id)->select('id'),
        )->pluck('path')->all();
        if ($perumahan->foto_path) {
            $paths[] = $perumahan->foto_path;
        }

        // Hard delete permanen: baris induk dihapus, psu_perumahans &
        // tipe_units ikut terhapus lewat foreign key cascadeOnDelete().
        DB::transaction(fn () => $perumahan->delete());
        Storage::disk('public')->delete($paths);

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

        return view('admin.perumahan.peta', [
            'titik' => $titik,
            'perumahans' => $perumahans,
            'kecamatanFokus' => $kecamatanFokus,
            'fokusAktif' => (int) (FilterQuery::ambil($request, [
                'kecamatan' => ['nullable', 'integer', 'min:1'],
            ])['kecamatan'] ?? 0) ?: null,
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
        $data = $request->validated();
        $this->syncPsu($perumahan, $data['psu'] ?? []);
        $this->syncTipeUnits($perumahan, $data['tipe_units'] ?? []);
    }

    private function pastikanFotoValid(StorePerumahanRequest $request): void
    {
        $foto = $request->file('foto');

        if ($foto !== null && ! $foto->isValid()) {
            throw ValidationException::withMessages([
                'foto' => 'Foto gagal diunggah. Ukuran file kemungkinan melebihi batas server. Coba kecilkan fotonya.',
            ]);
        }
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
