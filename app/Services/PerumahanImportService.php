<?php

namespace App\Services;

use App\Models\Kelurahan;
use App\Models\Perumahan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Impor data perumahan dari file CSV / XLSX / XLS sesuai format template.
 *
 * Prinsip: semua baris divalidasi lebih dulu. Bila ada satu saja baris
 * bermasalah, TIDAK ADA data yang masuk (all-or-nothing) dan seluruh
 * kesalahan dikumpulkan agar pengguna bisa memperbaiki file sekaligus.
 */
class PerumahanImportService
{
    private const BATAS_BARIS = 500;

    /**
     * Kolom template + alias yang diterima pada baris header
     * (toleran terhadap perbedaan penulisan, mis. "Luas Lahan").
     */
    private const KOLOM = [
        'nama' => ['nama', 'nama_perumahan'],
        'pengembang' => ['pengembang', 'developer'],
        'kecamatan' => ['kecamatan'],
        'kelurahan' => ['kelurahan', 'kelurahan_desa', 'desa', 'kelurahan_or_desa'],
        'alamat' => ['alamat', 'alamat_lengkap'],
        'rt' => ['rt'],
        'rw' => ['rw'],
        'latitude' => ['latitude', 'lat', 'lintang'],
        'longitude' => ['longitude', 'lng', 'long', 'bujur'],
        'luas_lahan' => ['luas_lahan', 'luas_lahan_m2', 'luas'],
        'jumlah_unit' => ['jumlah_unit', 'jumlah_unit_rumah', 'unit'],
        'status' => ['status', 'status_perumahan'],
        'tahun_pembuatan' => ['tahun_pembuatan', 'tahun_dibangun'],
        'tahun_selesai' => ['tahun_selesai', 'tahun_selesainya'],
        'jumlah_rth' => ['jumlah_rth', 'jumlah_titik_rth'],
        'luas_rth' => ['luas_rth', 'luas_rth_m2'],
        'kdb' => ['kdb'],
        'kdh' => ['kdh'],
        'ada_lahan_pemakaman' => ['ada_lahan_pemakaman', 'ada_pemakaman'],
        'luas_lahan_pemakaman' => ['luas_lahan_pemakaman', 'luas_pemakaman'],
        'lokasi_lahan_pemakaman' => ['lokasi_lahan_pemakaman', 'lokasi_pemakaman'],
        'keterangan' => ['keterangan', 'catatan'],
    ];

    public function headerTemplate(): array
    {
        return array_keys(self::KOLOM);
    }

    /**
     * Baca sheet pertama file dan kembalikan baris-baris data
     * yang sudah dinormalisasi (header jadi kunci kolom).
     */
    public function bacaBaris(UploadedFile $file): array
    {
        $spreadsheet = $this->muatSpreadsheet($file);

        try {
            $sheet = $spreadsheet->getSheet(0);
            $tabel = $sheet !== null ? $sheet->toArray(null, true, true, false) : [];

            if ($tabel === []) {
                throw ValidationException::withMessages([
                    'file' => 'Tidak ada baris data pada file. Baris pertama wajib berisi judul kolom.',
                ]);
            }

            $header = array_shift($tabel);
            $peta = $this->petakanHeader($header);

            if ($peta === []) {
                throw ValidationException::withMessages([
                    'file' => 'Baris judul kolom tidak dikenali. Unduh template di halaman ini, isi datanya tanpa mengganti nama judul.',
                ]);
            }

            $baris = [];
            foreach ($tabel as $data) {
                $norm = [];
                foreach ($peta as $indeks => $kunci) {
                    $nilai = $data[$indeks] ?? null;
                    $norm[$kunci] = $this->rapi($nilai);
                }
                $baris[] = $norm;
            }

            // Baris kosong (di Excel sering tersisa) diabaikan.
            $padat = array_values(array_filter($baris, fn (array $r) => array_filter($r, fn ($v) => $v !== '' && $v !== null) !== []));

            if ($padat === []) {
                throw ValidationException::withMessages([
                    'file' => 'File tidak berisi data apa pun. Gunakan template yang bisa diunduh pada halaman ini.',
                ]);
            }

            if (count($padat) > self::BATAS_BARIS) {
                throw ValidationException::withMessages([
                    'file' => sprintf('Maksimal %d baris data per file (file ini berisi %d baris).', self::BATAS_BARIS, count($padat)),
                ]);
            }

            return $padat;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Proses file: validasi tiap baris lalu simpan semuanya.
     * Mengembalikan jumlah data perumahan yang berhasil disimpan.
     */
    public function impor(UploadedFile $file): int
    {
        $barisData = $this->bacaBaris($file);

        $kesalahan = [];
        $sisipan = [];
        foreach ($barisData as $i => $row) {
            $nomor = $i + 2; // +2: baris template ke-1 dipakai judul kolom
            try {
                $sisipan[] = $this->validasiBaris($row);
            } catch (ValidationException $e) {
                foreach (array_merge(...array_values($e->errors())) as $pesan) {
                    $kesalahan[] = sprintf('Baris %d: %s', $nomor, $pesan);
                }
            }
        }

        if ($kesalahan !== []) {
            throw ValidationException::withMessages([
                'file' => array_merge(
                    ['File memuat baris yang belum benar, sehingga data TIDAK disimpan. Perbaiki berikut ini lalu unggah ulang:'],
                    $kesalahan
                ),
            ]);
        }

        \DB::transaction(function () use ($sisipan): void {
            foreach ($sisipan as $data) {
                Perumahan::create($data);
            }
        });

        return count($sisipan);
    }

    /** Validasi & konversi nilai baris menjadi isi $fillable Perumahan. */
    private function validasiBaris(array $row): array
    {
        // Kolom yang tidak ada di header file tetap dianggap kosong.
        foreach (array_keys(self::KOLOM) as $field) {
            $row[$field] ??= null;
        }

        $data = [
            'nama' => $row['nama'] ?? null,
            'pengembang' => $row['pengembang'] ?: null,
            'alamat' => $row['alamat'] ?: null,
            'rt' => $this->potong($row['rt'] ?? null, 5),
            'rw' => $this->potong($row['rw'] ?? null, 5),
            'kelurahan_id' => $this->resolusiKelurahan($row),
            'latitude' => $this->angka($row['latitude'] ?? null),
            'longitude' => $this->angka($row['longitude'] ?? null),
            'luas_lahan' => $this->angka($row['luas_lahan'] ?? null),
            'jumlah_unit' => $this->angka($row['jumlah_unit'] ?? null),
            'status' => $this->olaStatus($row['status'] ?? null),
            'tahun_pembuatan' => $this->angka($row['tahun_pembuatan'] ?? null) !== null
                ? (int) $this->angka($row['tahun_pembuatan'] ?? null) : null,
            'tahun_selesai' => $this->angka($row['tahun_selesai'] ?? null) !== null
                ? (int) $this->angka($row['tahun_selesai'] ?? null) : null,
            'jumlah_rth' => $this->angka($row['jumlah_rth'] ?? null),
            'luas_rth' => $this->angka($row['luas_rth'] ?? null),
            'kdb' => $this->angka($row['kdb'] ?? null),
            'kdh' => $this->angka($row['kdh'] ?? null),
            'keterangan' => $row['keterangan'] ?: null,
        ];

        $adaPemakaman = $this->boolean($row['ada_lahan_pemakaman'] ?? null);

        $data['ada_lahan_pemakaman'] = $adaPemakaman;
        $data['luas_lahan_pemakaman'] = $adaPemakaman ? ($this->angka($row['luas_lahan_pemakaman'] ?? null)) : null;
        $data['lokasi_lahan_pemakaman'] = $adaPemakaman && filled($row['lokasi_lahan_pemakaman'] ?? null)
            ? (string) $row['lokasi_lahan_pemakaman'] : null;

        $validator = Validator::make($data, [
            'nama' => ['required', 'string', 'max:150'],
            'kelurahan_id' => ['required', 'integer', 'exists:kelurahans,id'],
            'pengembang' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'luas_lahan' => ['required', 'numeric', 'min:0'],
            'jumlah_unit' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_keys(Perumahan::STATUS))],
            'tahun_pembuatan' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'tahun_selesai' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 10)],
            'jumlah_rth' => ['nullable', 'integer', 'min:0'],
            'luas_rth' => ['nullable', 'numeric', 'min:0'],
            'kdb' => ['nullable', 'numeric', 'between:0,100'],
            'kdh' => ['nullable', 'numeric', 'between:0,100'],
            'ada_lahan_pemakaman' => ['boolean'],
            'luas_lahan_pemakaman' => ['nullable', 'numeric', 'min:0'],
            'lokasi_lahan_pemakaman' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ], [
            'nama.required' => 'Kolom nama wajib diisi.',
            'nama.max' => 'Kolom nama maksimal 150 karakter.',
            'kelurahan_id.required' => 'Kolom kelurahan wajib diisi sesuai daftar di menu Wilayah.',
            'kelurahan_id.exists' => 'Kelurahan tidak ditemukan pada data Wilayah. Cek ejaan atau tambahkan dulu di menu Wilayah.',
            'luas_lahan.required' => 'Kolom luas lahan wajib diisi.',
            'jumlah_unit.required' => 'Kolom jumlah unit wajib diisi.',
            'jumlah_unit.integer' => 'Kolom jumlah unit harus berupa angka bulat.',
            'status.required' => 'Kolom status wajib diisi (contoh: komersil).',
            'status.in' => 'Kolom status harus salah satu dari: '.implode(', ', array_keys(Perumahan::STATUS)).'.',
            'tahun_pembuatan.min' => 'Tahun pembuatan tidak wajar.',
            'tahun_pembuatan.max' => 'Tahun pembuatan tidak wajar.',
            'tahun_selesai.min' => 'Tahun selesai tidak wajar.',
            'tahun_selesai.max' => 'Tahun selesai tidak wajar.',
            'kdb.between' => 'Kolom KDB harus di antara 0 dan 100.',
            'kdh.between' => 'Kolom KDH harus di antara 0 dan 100.',
            'keterangan.max' => 'Kolom keterangan maksimal 2000 karakter.',
        ]);

        $gagal = $validator->fails() ? $validator->errors()->all() : [];

        if ($gagal === []) {
            $gagal = $this->validasiTambahan($data);
        }

        if ($gagal !== []) {
            throw ValidationException::withMessages(['file' => $gagal]);
        }

        // Kolom NOT NULL dengan default di tabel (luasan & angka) tidak boleh
        // dikirim sebagai null — hilangkan saja jadi memakai default DB.
        foreach (['luas_lahan', 'jumlah_unit', 'jumlah_rth', 'luas_rth'] as $field) {
            if ($data[$field] === null) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /** Validasi antar kolom yang tergantung kolom lainnya. */
    private function validasiTambahan(array $data): array
    {
        $gagal = [];

        if ($data['tahun_pembuatan'] !== null && $data['tahun_selesai'] !== null
            && $data['tahun_selesai'] < $data['tahun_pembuatan']) {
            $gagal[] = 'Tahun selesai tidak boleh lebih awal dari tahun pembuatan.';
        }

        if ($data['luas_rth'] !== null && (float) $data['luas_rth'] > (float) $data['luas_lahan']) {
            $gagal[] = 'Luas RTH tidak boleh lebih besar daripada luas lahan.';
        }

        $batas = config('peta.batas');
        foreach ([
            'latitude' => ['lintang', $batas['latitude_min'], $batas['latitude_max']],
            'longitude' => ['bujur', $batas['longitude_min'], $batas['longitude_max']],
        ] as $field => [$label, $min, $max]) {
            $nilai = $data[$field];
            if ($nilai === null) {
                continue;
            }
            if ((float) $nilai < (float) $min || (float) $nilai > (float) $max) {
                $gagal[] = sprintf(
                    'Koordinat %s (%s) di luar batas wilayah %s (%s s.d. %s).',
                    $label, $nilai,
                    config('peta.nama_wilayah'), $min, $max
                );
            }
        }

        return $gagal;
    }

    /**
     * Cocokkan nama kelurahan (dan opsional kecamatan) dengan data Wilayah.
     * Pencocokan tidak memandang kapitalisasi.
     */
    private function resolusiKelurahan(array $row): ?int
    {
        $namaKelurahan = trim((string) ($row['kelurahan'] ?? ''));
        $namaKecamatan = trim((string) ($row['kecamatan'] ?? ''));

        if ($namaKelurahan === '') {
            return null;
        }

        $query = Kelurahan::whereRaw('lower(nama) = ?', [$this->hurufKecil($namaKelurahan)]);
        if ($namaKecamatan !== '') {
            $query->whereHas('kecamatan', fn ($q) => $q
                ->whereRaw('lower(nama) = ?', [$this->hurufKecil($namaKecamatan)]));
        }

        $kelurahans = $query->orderBy('id')->get();

        if ($kelurahans->count() > 1 && $namaKecamatan === '') {
            $kecamatanBede = $kelurahans->map(fn ($k) => $k->kecamatan->nama)->unique()->implode(', ');

            throw ValidationException::withMessages(['file' => [
                "Kelurahan \"{$namaKelurahan}\" ada di beberapa kecamatan ({$kecamatanBede}). Isi kolom kecamatan pada baris ini.",
            ]]);
        }

        return $kelurahans->first()?->id;
    }

    /** Status perumahan: terima kode maupun label ("Subsidi"). Default komersil bila kosong. */
    private function olaStatus(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '') {
            return 'komersil';
        }

        $kunci = $this->hurufKecil($status);
        foreach (Perumahan::STATUS as $kode => $label) {
            if ($kunci === $kode || $kunci === $this->hurufKecil($label)) {
                return $kode;
            }
        }

        return 'tidak_dikenal'; // digagalkan oleh aturan 'in' validator
    }

    /**
     * Angka toleran: terima 1234.56, 1.234.567,89 (Indonesia), 1,234,567.89,
     * maupun angka dari sel Excel. Mengembalikan null bila sel kosong,
     * dan menjalankan gagal() bila tidak bisa dipahami sebagai angka.
     */
    private function angka(mixed $nilai): ?float
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }

        $teks = trim(str_replace("\xC2\xA0", '', (string) $nilai));

        if (is_numeric($teks)) {
            return (float) $teks;
        }

        // Format berpemisah ribuan.
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $teks) === 1) {
            $teks = str_replace(['.', ','], ['', '.'], $teks);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $teks) === 1) {
            $teks = str_replace(',', '', $teks);
        } elseif (preg_match('/^(-?\d+),(\d{1,2})$/', $teks, $m) === 1) {
            // Koma desimal gaya Indonesia: "12,5".
            return (float) ($m[1].'.'.$m[2]);
        }

        if (is_numeric($teks)) {
            return (float) $teks;
        }

        $this->gagal(sprintf('Kolom ini berisi nilai yang bukan angka yang wajar: "%s".', $teks));

        return null; // tidak tercapai: gagal() selalu melempar
    }

    /** Terima ya/tidak, ada/tidak, true/false, 1/0. Kosong dianggap tidak ada. */
    private function boolean(mixed $nilai): bool
    {
        $kunci = $this->hurufKecil(trim((string) ($nilai ?? '')));

        if (in_array($kunci, ['ya', 'true', 'ada', '1', 'yes', 'v'], true)) {
            return true;
        }

        return false;
    }

    /** Potong teks RT/RW agar muat di kolom string(5). */
    private function potong(mixed $nilai, int $panjang): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);

        return $teks === '' ? null : substr($teks, 0, $panjang);
    }

    /** Buang spasi berlebih & konversi angka Excel "contoh 2024.0"? tetap teks. */
    private function rapi(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_float($nilai) || is_int($nilai)) {
            // 1500.0 berasal dari sel srv Excel, kembalikan ke "1500" saat bulat.
            if ((float) $nilai === floor((float) $nilai)) {
                return (string) (int) $nilai;
            }

            return (string) $nilai;
        }

        $teks = trim(str_replace("\xC2\xA0", ' ', (string) $nilai));

        return $teks === '' ? null : $teks;
    }

    private function hurufKecil(string $teks): string
    {
        return mb_strtolower($teks, 'UTF-8');
    }

    /** Lempar kesalahan pemrosesan yang dikumpulkan sebagai pesan pada field file. */
    private function gagal(string $pesan): never
    {
        throw ValidationException::withMessages(['file' => [$pesan]]);
    }

    /**
     * Muat file sesuai ekstensi, dan deteksi otomatis delimiter CSV
     * (Microsoft Excel di zona id-ID menyimpan CSV pakai ';').
     */
    private function muatSpreadsheet(UploadedFile $file): Spreadsheet
    {
        $path = $file->getRealPath();
        $ekstensi = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');

        try {
            if ($ekstensi === 'csv') {
                $pembaca = new Csv();
                $pembaca->setDelimiter($this->tebakDelimiter($path));
                $pembaca->setInputEncoding('UTF-8');
                $spreadsheet = $pembaca->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'file' => 'File tidak dapat dibaca. Pastikan berformat CSV / XLSX / XLS dan tidak rusak.',
            ]);
        }

        return $spreadsheet;
    }

    private function tebakDelimiter(string $path): string
    {
        $barisPertama = strtok((string) @file_get_contents($path, false, null, 0, 2048), "\r\n");
        $barisPertama = is_string($barisPertama) ? $barisPertama : '';

        $porsi = [
            ',' => substr_count($barisPertama, ','),
            ';' => substr_count($barisPertama, ';'),
            "\t" => substr_count($barisPertama, "\t"),
        ];
        arsort($porsi);

        $terkuat = array_key_first($porsi);

        return $porsi[$terkuat] > 0 ? $terkuat : ',';
    }

    /** Petakan indeks kolom ke nama field lewat tabel alias KOLOM. */
    private function petakanHeader(array $header): array
    {
        $peta = [];
        $dipakai = [];

        foreach ($header as $indeks => $judul) {
            $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(trim((string) $judul), 'UTF-8')), '_');

            $kunci = null;
            foreach (self::KOLOM as $field => $alias) {
                if (in_array($slug, $alias, true)) {
                    $kunci = $field;
                    break;
                }
            }

            if ($kunci !== null && ! isset($dipakai[$kunci])) {
                $peta[$indeks] = $kunci;
                $dipakai[$kunci] = true;
            }
        }

        return $peta;
    }
}
