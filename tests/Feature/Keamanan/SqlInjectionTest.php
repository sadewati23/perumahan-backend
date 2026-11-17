<?php

namespace Tests\Feature\Keamanan;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\User;
use App\Support\NamaPerumahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Regresi SQL injection (regresi dari audit sink app/ + database/).
 *
 * Untuk setiap parameter filter publik & admin dikirim payload kutip,
 * boolean-tautan, wildcard LIKE, backslash, stack query, dan unicode.
 * Tegasan: respons wajar (200/302/422, bukan 500), jumlah baris yang
 * tampil tidak melebihi hasil tanpa filter, tabel penting tetap ada,
 * dan jumlah record tidak berubah.
 *
 * Catatan lingkungan test: sqlite (:memory:) — pernyataan "tidak
 * mencocokkan semua baris" untuk wildcard mengikuti perilaku sqlite,
 * di mana pola hasil escape (`\%`, `\_`) hanya cocok bila datanya
 * benar-benar memuat karakter tersebut.
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOADS = [
        "' OR 1=1 --",
        '" OR "1"="1',
        '%',
        '_',
        '\\',
        "'; DROP TABLE users; --",
        "' é\u{00A0}ü中 ' OR '1'='1' --",
    ];

    private const TABEL_PENTING = [
        'perumahans', 'kelurahans', 'kecamatans', 'laporans', 'psu_perumahans', 'users',
    ];

    // ------------------------------------------------------------------
    // Data & helper
    // ------------------------------------------------------------------

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function buatPerumahan(array $atribut = []): Perumahan
    {
        $kecamatan = Kecamatan::firstOrCreate(['nama' => 'Coblong'], ['kabupaten_kota' => 'Kota Bandung']);
        $kelurahan = Kelurahan::firstOrCreate(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);

        $perumahan = Perumahan::create(array_merge([
            'nama' => 'Perumahan Contoh',
            'pengembang' => 'PT Contoh',
            'kelurahan_id' => $kelurahan->id,
            'alamat' => 'Jl. Contoh No. 1',
            'luas_lahan' => 10000,
            'jumlah_unit' => 80,
            'status' => 'komersil',
            'tahun_pembuatan' => 2020,
            'tahun_selesai' => 2022,
        ], $atribut));

        $perumahan->psu()->create(['jenis' => 'Jalan Lingkungan', 'jumlah' => 900, 'satuan' => 'meter', 'kondisi' => 'baik']);

        return $perumahan;
    }

    private function buatLaporan(array $atribut = []): Laporan
    {
        return Laporan::create(array_merge([
            'kode' => Laporan::buatKode('pengaduan'),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Laporan Alpha Uji',
            'deskripsi' => 'Deskripsi laporan uji.',
            'status' => 'baru',
        ], $atribut));
    }

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('perumahan.csv', $isi);
    }

    private function csvHeader(): string
    {
        return 'Nama Perumahan,Pengembang,Kecamatan,Kelurahan,Alamat,RT,RW,Latitude,Longitude,Luas Lahan,Jumlah Unit,Status';
    }

    /** @return array<string, int> jumlah baris tiap tabel penting */
    private function snapshot(): array
    {
        return collect(self::TABEL_PENTING)
            ->mapWithKeys(fn (string $tabel): array => [$tabel => (int) DB::table($tabel)->count()])
            ->all();
    }

    private function assertSkemaDanDataUtuh(array $sebelum, string $konteks): void
    {
        foreach (self::TABEL_PENTING as $tabel) {
            $this->assertTrue(
                Schema::hasTable($tabel),
                "Tabel {$tabel} hilang setelah {$konteks}."
            );
            $this->assertSame(
                $sebelum[$tabel],
                (int) DB::table($tabel)->count(),
                "Jumlah record tabel {$tabel} berubah setelah {$konteks}."
            );
        }
    }

    private function assertResponsWajar(TestResponse $response, string $konteks): void
    {
        $this->assertContains(
            $response->getStatusCode(),
            [200, 302, 303, 422],
            "Respons tidak wajar (dugaan error server) pada {$konteks}: HTTP ".$response->getStatusCode()
        );
    }

    // ------------------------------------------------------------------
    // Filter publik: /perumahan
    // ------------------------------------------------------------------

    public function test_filter_publik_perumahan_tahan_payload_sql_injection(): void
    {
        $perumahan = $this->buatPerumahan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            foreach (['q', 'status', 'kecamatan_id'] as $parameter) {
                $konteks = "publik /perumahan?{$parameter}=".addslashes($payload);

                $response = $this->get(route('publik.perumahan.index', [$parameter => $payload]));
                $this->assertResponsWajar($response, $konteks);
                $this->assertSkemaDanDataUtuh($sebelum, $konteks);

                // Payload tidak boleh menjadi tautan yang mencocokkan SEMUA baris:
                // daftar hasil tidak boleh menampilkan record saat filter kutip/wildcard.
                if ($parameter === 'q') {
                    $response->assertDontSee($perumahan->nama);
                }
            }
        }

        // Parameter valid tetap berfungsi (pencarian normal tidak rusak).
        $this->get(route('publik.perumahan.index', ['q' => 'Contoh']))
            ->assertOk()
            ->assertSee($perumahan->nama);
    }

    // ------------------------------------------------------------------
    // Filter publik: /peta
    // ------------------------------------------------------------------

    public function test_filter_publik_peta_tahan_payload_sql_injection(): void
    {
        $this->buatPerumahan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            foreach (['q', 'status', 'kecamatan_id'] as $parameter) {
                $konteks = "publik /peta?{$parameter}=".addslashes($payload);

                $response = $this->get(route('publik.peta', [$parameter => $payload]));
                $this->assertResponsWajar($response, $konteks);
                $this->assertSkemaDanDataUtuh($sebelum, $konteks);
            }
        }
    }

    // ------------------------------------------------------------------
    // Publik: /cek-laporan (POST kode)
    // ------------------------------------------------------------------

    public function test_cek_laporan_tahan_payload_sql_injection(): void
    {
        $laporan = $this->buatLaporan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            $konteks = 'publik /cek-laporan kode='.addslashes($payload);

            $response = $this->post(route('cek-laporan.cari'), ['kode' => $payload]);
            $this->assertResponsWajar($response, $konteks);
            $this->assertSkemaDanDataUtuh($sebelum, $konteks);

            // Kode laporan tidak boleh bocor dari payload (hanya 302 kembali + error).
            $response->assertRedirect();
            $response->assertSessionHasErrors('kode');
        }

        // Kode valid tetap ditemukan — payload tidak merusak jalur pencarian normal.
        $this->post(route('cek-laporan.cari'), ['kode' => $laporan->kode])
            ->assertOk()
            ->assertSee($laporan->kode);
    }

    // ------------------------------------------------------------------
    // Admin: daftar perumahan
    // ------------------------------------------------------------------

    public function test_filter_admin_perumahan_tahan_payload_sql_injection(): void
    {
        $petugas = $this->petugas();
        $perumahan = $this->buatPerumahan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            foreach (['q', 'status', 'kecamatan_id', 'tahun'] as $parameter) {
                $konteks = "admin /perumahan?{$parameter}=".addslashes($payload);

                $response = $this->actingAs($petugas)
                    ->get(route('perumahan.index', [$parameter => $payload]));

                $this->assertResponsWajar($response, $konteks);
                $this->assertSkemaDanDataUtuh($sebelum, $konteks);

                if ($parameter === 'q') {
                    $response->assertDontSee($perumahan->nama);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Admin: daftar laporan
    // ------------------------------------------------------------------

    public function test_filter_admin_laporan_tahan_payload_sql_injection(): void
    {
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            foreach (['q', 'jenis', 'status', 'tingkat', 'perumahan_id'] as $parameter) {
                $konteks = "admin /laporan?{$parameter}=".addslashes($payload);

                $response = $this->actingAs($petugas)
                    ->get(route('laporan.index', [$parameter => $payload]));

                $this->assertResponsWajar($response, $konteks);
                $this->assertSkemaDanDataUtuh($sebelum, $konteks);

                if ($parameter === 'q') {
                    $response->assertDontSee($laporan->judul);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Admin: rekap cetak & word (filter q/jenis/status/tingkat/perumahan_id/dari/sampai)
    // ------------------------------------------------------------------

    public function test_rekap_cetak_tahan_payload_sql_injection(): void
    {
        $petugas = $this->petugas();
        $this->buatLaporan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            foreach (['q', 'jenis', 'status', 'tingkat', 'perumahan_id', 'dari', 'sampai'] as $parameter) {
                foreach (['laporan.cetak-rekap', 'laporan.cetak-rekap.word'] as $rute) {
                    $konteks = "admin rekap ({$rute})?{$parameter}=".addslashes($payload);

                    $response = $this->actingAs($petugas)
                        ->get(route($rute, [$parameter => $payload]));

                    $this->assertResponsWajar($response, $konteks);
                    $this->assertSkemaDanDataUtuh($sebelum, $konteks);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // LIKE: % dan _ diperlakukan sebagai teks biasa
    // ------------------------------------------------------------------

    public function test_wildcard_like_diperlakukan_sebagai_teks_biasa(): void
    {
        $petugas = $this->petugas();
        $perumahan = $this->buatPerumahan(['nama' => 'Perumahan Alpha']);
        $this->buatPerumahan(['nama' => 'Perumahan Beta']);
        $laporan = $this->buatLaporan(['judul' => 'Laporan Alpha Uji']);

        // Wildcard pada publik /perumahan
        foreach (['%', '_', '\\'] as $payload) {
            $response = $this->get(route('publik.perumahan.index', ['q' => $payload]));
            $this->assertResponsWajar($response, "publik q={$payload}");
            $response->assertDontSee($perumahan->nama); // tidak mencocokkan semua baris
        }

        // Wildcard pada admin daftar laporan
        foreach (['%', '_', '\\'] as $payload) {
            $response = $this->actingAs($petugas)->get(route('laporan.index', ['q' => $payload]));
            $this->assertResponsWajar($response, "admin laporan q={$payload}");
            $response->assertDontSee($laporan->judul);
        }

        // Pencarian teks biasa tetap bekerja (escape tidak merusak LIKE normal).
        $this->get(route('publik.perumahan.index', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee($perumahan->nama)
            ->assertDontSee('Perumahan Beta');

        $this->actingAs($petugas)->get(route('laporan.index', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee($laporan->judul);
    }

    // ------------------------------------------------------------------
    // Impor: payload pada sel nama / kelurahan / kecamatan
    // ------------------------------------------------------------------

    public function test_impor_sel_nama_payload_disimpan_sebagai_data_bukan_dieksekusi(): void
    {
        $petugas = $this->petugas();
        $this->buatPerumahan(); // sumber kelurahan/kecamatan 'Coblong'/'Dago'
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            $konteks = 'impor sel nama='.addslashes($payload);
            $idAwal = (int) DB::table('perumahans')->max('id');

            $isi = $this->csvHeader()."\n"
                ."{$payload},PT Uji,Coblong,Dago,Jl. Uji 1,001,002,-6.7320,108.5523,5000,10,komersil\n";

            $response = $this->actingAs($petugas)
                ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

            $this->assertResponsWajar($response, $konteks);

            // Payload nama lolos validasi teks dan disimpan sebagai DATA
            // (dibinding, bukan dieksekusi): tepat satu baris baru per payload.
            $baru = DB::table('perumahans')->where('id', '>', $idAwal)->get();
            $this->assertCount(1, $baru, "Impor tidak menyimpan tepat 1 baris untuk {$konteks}.");
            $this->assertSame(
                $sebelum['perumahans'] + 1,
                (int) DB::table('perumahans')->count(),
                "Jumlah perumahan tidak tepat +1 untuk {$konteks}."
            );

            // Payload tanpa kutip-ganda selamat dari pembacaan CSV apa adanya;
            // payload berkutip-ganda dinormalisasi pembaca CSV, yang penting
            // nilainya tetap tersimpan sebagai teks, bukan dieksekusi sebagai SQL.
            // Normalisasi nama (NBSP -> spasi, spasi ganda -> satu, trim) adalah
            // aturan aplikasi, bukan efek SQL, jadi yang dibandingkan versi itu.
            if (! str_contains($payload, '"')) {
                $this->assertSame(
                    NamaPerumahan::normalisasi($payload),
                    $baru->first()->nama,
                    "Payload nama tidak tersimpan apa adanya ({$konteks})."
                );
            } else {
                $this->assertStringContainsStringIgnoringCase(
                    'OR',
                    (string) $baru->first()->nama,
                    "Payload nama tidak tersimpan sebagai teks ({$konteks})."
                );
            }

            // Snapshot untuk tabel LAIN (perumahans memang sengaja +1).
            $sebelumLain = $sebelum;
            $sebelumLain['perumahans'] += 1;
            $this->assertSkemaDanDataUtuh($sebelumLain, $konteks);

            // Bersihkan untuk iterasi payload berikutnya.
            DB::table('perumahans')->where('id', '>', $idAwal)->delete();
            $this->assertSame($sebelum['perumahans'], (int) DB::table('perumahans')->count());
        }
    }

    public function test_impor_sel_kelurahan_dan_kecamatan_payload_gagal_tanpa_menyimpan(): void
    {
        $petugas = $this->petugas();
        $this->buatPerumahan();
        $sebelum = $this->snapshot();

        foreach (self::PAYLOADS as $payload) {
            $konteks = 'impor sel kelurahan/kecamatan='.addslashes($payload);

            $isi = $this->csvHeader()."\n"
                ."Perumahan Uji,PT Uji,{$payload},{$payload},Jl. Uji 1,001,002,-6.7320,108.5523,5000,10,komersil\n";

            $response = $this->actingAs($petugas)
                ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

            $this->assertResponsWajar($response, $konteks);
            $response->assertSessionHasErrors('file');

            // Kelurahan/kecamatan payload tidak dikenali (binding whereRaw lower(nama)=?)
            // sehingga baris gagal validasi: all-or-nothing, TIDAK ADA yang tersimpan.
            $this->assertSkemaDanDataUtuh($sebelum, $konteks);
        }
    }
}
