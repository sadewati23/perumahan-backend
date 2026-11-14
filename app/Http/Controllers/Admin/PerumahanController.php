<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportPerumahanRequest;
use App\Http\Requests\Admin\StorePerumahanRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use App\Services\PerumahanImportService;
use App\Services\PerumahanPetaService;
use App\Support\NamaPerumahan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\FilterQuery;
use Illuminate\Database\UniqueConstraintViolationException;
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
                $fotoPath = \App\Support\FotoBersih::simpan($request->file('foto'), 'perumahan', 'public');
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
        } catch (UniqueConstraintViolationException $exception) {
            // Balapan dua permintaan bersamaan: unique index menangkap duplikat
            // yang lolos dari validasi. Kembalikan sebagai pesan validasi, bukan 500.
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $this->galatDuplikat($data['nama'], (int) $data['kelurahan_id']);
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
                $fotoPath = \App\Support\FotoBersih::simpan($request->file('foto'), 'perumahan', 'public');
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
        } catch (UniqueConstraintViolationException $exception) {
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $this->galatDuplikat($data['nama'], (int) $data['kelurahan_id'], $perumahan->getKey());
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

    /**
     * Pesan validasi untuk bentrok unique index (nama + kelurahan_id).
     * Record baru tidak disimpan; pengguna diminta memilih nama/kelurahan lain.
     */
    private function galatDuplikat(string $nama, int $kelurahanId, ?int $kecualiId = null): ValidationException
    {
        $bentrok = Perumahan::query()
            ->where('kelurahan_id', $kelurahanId)
            ->when($kecualiId !== null, fn ($query) => $query->where('id', '!=', $kecualiId))
            ->get(['id', 'nama'])
            ->first(fn (Perumahan $perumahan) => NamaPerumahan::sama($perumahan->nama, $nama));

        $kelurahan = Kelurahan::with('kecamatan')->find($kelurahanId);

        return ValidationException::withMessages([
            'nama' => sprintf(
                '%s sudah terdaftar di %s. Gunakan nama lain atau pilih kelurahan yang berbeda.',
                $bentrok ? 'Perumahan "'.$bentrok->nama.'"' : 'Perumahan dengan nama tersebut',
                $kelurahan?->nama_lengkap ?? 'kelurahan yang dipilih',
            ),
        ]);
    }

    public function destroy(Perumahan $perumahan): RedirectResponse
    {
        $nama = $perumahan->nama;
        $fotoPerumahan = $perumahan->foto_path;
        $pathsLaporan = LaporanFoto::whereIn(
            'laporan_id',
            Laporan::where('perumahan_id', $perumahan->id)->select('id'),
        )->pluck('path')->filter()->values()->all();

        // Hard delete permanen: baris induk dihapus, psu_perumahans &
        // tipe_units ikut terhapus lewat foreign key cascadeOnDelete().
        DB::transaction(fn () => $perumahan->delete());

        // Foto laporan berada di disk privat, foto perumahan tetap di disk publik.
        if ($pathsLaporan !== []) {
            Storage::disk(LaporanFoto::DISK)->delete($pathsLaporan);
        }
        if ($fotoPerumahan) {
            Storage::disk('public')->delete($fotoPerumahan);
        }

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
     * Halaman impor data perumahan dari file CSV / XLSX / XLS.
     * Tersedia untuk admin maupun petugas.
     */
    public function import(): View
    {
        return view('admin.perumahan.import', [
            'kolom' => $this->kolomTemplate(),
        ]);
    }

    public function simpanImport(ImportPerumahanRequest $request): RedirectResponse
    {
        $jumlah = app(PerumahanImportService::class)->impor($request->file('file'));

        $this->peta->hapusCache();
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('perumahan.index')
            ->with('success', "Impor berhasil. {$jumlah} data perumahan baru telah ditambahkan.");
    }

    /** Template kosong CSV (pemisah koma). */
    public function templateCsv(): StreamedResponse
    {
        $kolom = $this->kolomTemplate();

        return response()->streamDownload(function () use ($kolom): void {
            $keluar = fopen('php://output', 'w');
            fwrite($keluar, "\xEF\xBB\xBF"); // BOM agar tidak berantakan di Excel
            fputcsv($keluar, $kolom);
            fputcsv($keluar, $this->barisContoh());
            fclose($keluar);
        }, 'template-impor-perumahan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Template kosong XLSX (+ contoh isi pada baris 2). */
    public function templateXlsx(): StreamedResponse
    {
        $kolom = $this->kolomTemplate();
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $barisAkhir = chr(ord('A') + count($kolom) - 1);
        $sheet->fromArray([$kolom, $this->barisContoh()], null, 'A1');
        $sheet->getStyle("A1:{$barisAkhir}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new XlsxWriter($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-impor-perumahan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Judul kolom template — cocok dengan alias pada PerumahanImportService. */
    private function kolomTemplate(): array
    {
        return [
            'Nama Perumahan',
            'Pengembang',
            'Kecamatan',
            'Kelurahan',
            'Alamat',
            'RT',
            'RW',
            'Latitude',
            'Longitude',
            'Luas Lahan',
            'Jumlah Unit',
            'Status Perumahan',
            'Tahun Pembuatan',
            'Tahun Selesai',
            'Jumlah RTH',
            'Luas RTH',
            'KDB',
            'KDH',
            'Ada Lahan Pemakaman',
            'Luas Lahan Pemakaman',
            'Lokasi Lahan Pemakaman',
            'Keterangan',
        ];
    }

    private function barisContoh(): array
    {
        return [
            'Contoh Perumahan ABC',
            'PT Pengembang',
            'Coblong',
            'Dago',
            'Jl. Contoh No. 12',
            '001',
            '002',
            -6.7320,
            108.5523,
            '15000',
            '100',
            'Subsidi',
            '2020',
            '2022',
            '2',
            '2000',
            '70',
            '40',
            'Ya',
            '500',
            'Blok D',
            'Baris contoh — hapus atau ganti sebelum diimpor',
        ];
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
