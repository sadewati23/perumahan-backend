<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private function buatPerumahan(string $nama = 'Perumahan Uji Lapor'): Perumahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);

        $perumahan = Perumahan::create([
            'nama' => $nama,
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $perumahan->psu()->create([
            'jenis' => 'Penerangan Jalan Umum',
            'jumlah' => 12,
            'satuan' => 'titik',
            'kondisi' => 'baik',
        ]);

        return $perumahan;
    }

    private function buatKelurahan(string $namaKelurahan = 'Ciburial', string $namaKecamatan = 'Cimenyan'): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => $namaKecamatan, 'kabupaten_kota' => 'Kab. Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => $namaKelurahan]);
    }

    private function dataLaporan(Perumahan $perumahan, array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'kerusakan',
            'perumahan_id' => $perumahan->id,
            'nama_pelapor' => 'Budi Santoso',
            'kontak_pelapor' => '081234567890',
            'judul' => 'PJU blok C mati semua',
            'deskripsi' => 'Lampu penerangan jalan di blok C mati sejak Senin lalu.',
            'tingkat' => 'rusak_ringan',
        ], $overrides);
    }

    private function dataPengaduan(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Siti Aminah',
            'kontak_pelapor' => '089876543210',
            'judul' => 'Usulan penambahan tong sampah',
            'deskripsi' => 'Mohon ditambah tong sampah di ujung jalan, sering penuh.',
        ], $overrides);
    }

    private function dataBangunanLiar(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'bangunan_liar',
            'nama_pelapor' => 'Agus Wijaya',
            'kontak_pelapor' => '081211223344',
            'judul' => 'Bangunan tambah tanpa izin di bantaran jalan',
            'deskripsi' => 'Ada bangunan tambah dua lantai dibangun di bantaran jalan tanpa papan nama pengembang.',
            'lokasi_teks' => 'Jl. Kenanga gang 4, di samping warung Bu Yati',
        ], $overrides);
    }

    public function test_publik_bisa_mengirim_laporan_tanpa_login(): void
    {
        $perumahan = $this->buatPerumahan();

        $response = $this->post(route('lapor.store'), $this->dataLaporan($perumahan));

        $laporan = Laporan::first();

        $response->assertRedirect(route('lapor.create'));
        $response->assertSessionHas('laporan_kode', $laporan->kode);

        $this->assertNotNull($laporan);
        $this->assertSame('baru', $laporan->status);
        $this->assertStringStartsWith('LPR-', $laporan->kode);
        $this->assertSame(0, $laporan->fotos()->count());
    }

    public function test_form_lapor_bisa_dibuka_tanpa_login_dan_mendukung_prefill_perumahan(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->get(route('lapor.create'))
            ->assertOk()
            ->assertSee('Pengaduan')
            ->assertSee('Bangunan Liar')
            ->assertSee($perumahan->nama);

        $this->get(route('lapor.create', ['perumahan' => $perumahan->id, 'jenis' => 'pengaduan']))
            ->assertOk();
    }

    public function test_laporan_bisa_melampirkan_foto_bukti(): void
    {
        Storage::fake('public');
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'foto' => [
                UploadedFile::fake()->image('bukti-1.jpg'),
                UploadedFile::fake()->image('bukti-2.png'),
            ],
        ]));

        $laporan = Laporan::first();

        $this->assertCount(2, $laporan->fotos);
        foreach ($laporan->fotos as $foto) {
            Storage::disk('public')->assertExists($foto->path);
            $this->assertStringStartsWith('laporan/', $foto->path);
        }
    }

    public function test_validasi_menolak_laporan_tidak_lengkap(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), ['perumahan_id' => $perumahan->id])
            ->assertSessionHasErrors(['nama_pelapor', 'kontak_pelapor', 'judul', 'deskripsi', 'tingkat']);

        $this->assertSame(0, Laporan::count());
    }

    public function test_menolak_lebih_dari_tiga_foto(): void
    {
        Storage::fake('public');
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'foto' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
            ],
        ]))->assertSessionHasErrors('foto');

        $this->assertSame(0, Laporan::count());
    }

    public function test_psu_yang_dilaporkan_harus_milik_perumahan_terpilih(): void
    {
        $perumahan = $this->buatPerumahan('Perumahan Uji A');
        $perumahanLain = $this->buatPerumahan('Perumahan Uji B');
        $psuLain = $perumahanLain->psu->first();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'psu_perumahan_id' => $psuLain->id,
        ]))->assertSessionHasErrors('psu_perumahan_id');

        $this->assertSame(0, Laporan::count());
    }

    public function test_hapus_laporan_juga_menghapus_foto_di_storage_dan_db(): void
    {
        Storage::fake('public');
        $perumahan = $this->buatPerumahan();
        $laporan = Laporan::create($this->dataLaporan($perumahan) + ['kode' => Laporan::buatKode()]);

        // Upload dua foto, simpan path yang sama untuk verifikasi nanti.
        $foto1 = UploadedFile::fake()->image('bukti.jpg');
        $pathFoto1 = $foto1->store('laporan', 'public');
        $laporan->fotos()->create(['path' => $pathFoto1]);
        $laporan->fotos()->create(['path' => UploadedFile::fake()->image('bukti2.png')->store('laporan', 'public')]);

        $this->actingAs(User::factory()->create(['role' => 'petugas']));

        $this->delete(route('laporan.destroy', $laporan))
            ->assertRedirect(route('laporan.index'));

        // Baris laporan + foto ikut hilang dari database.
        $this->assertDatabaseMissing('laporans', ['id' => $laporan->id]);
        $this->assertSame(0, Laporan::count());
        $this->assertDatabaseMissing('laporan_fotos', ['laporan_id' => $laporan->id]);

        // Foto dipindahkan dari storage public (tidak ada lagi di disk).
        Storage::disk('public')->assertMissing($pathFoto1);
    }

    public function test_hapus_laporan_hanya_menghapus_laporan_bukan_perumahan(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'persentase_kerusakan' => 45.5,
            'panjang_kerusakan' => 12.5,
        ]));

        $laporan = Laporan::first();

        $this->assertNotNull($laporan);
        $this->assertEquals(45.5, (float) $laporan->persentase_kerusakan);
        $this->assertEquals(12.5, (float) $laporan->panjang_kerusakan);

        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get(route('laporan.show', $laporan))
            ->assertOk()
            ->assertSee('45,5 %')
            ->assertSee('12,5 m');

        $this->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('45,5 % · 12,5 m');
    }

    public function test_validasi_ukuran_kerusakan(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'persentase_kerusakan' => 150,
        ]))->assertSessionHasErrors('persentase_kerusakan');

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, [
            'panjang_kerusakan' => -5,
        ]))->assertSessionHasErrors('panjang_kerusakan');

        $this->assertSame(0, Laporan::count());
    }

    public function test_tamu_tidak_bisa_membuka_kelola_laporan(): void
    {
        $this->get(route('laporan.index'))->assertRedirect(route('login'));
    }

    public function test_petugas_bisa_membuka_daftar_dan_detail_laporan(): void
    {
        $perumahan = $this->buatPerumahan();
        $laporan = Laporan::create($this->dataLaporan($perumahan) + ['kode' => Laporan::buatKode()]);

        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee($laporan->kode)
            ->assertSee($laporan->judul);

        $this->get(route('laporan.show', $laporan))
            ->assertOk()
            ->assertSee($laporan->kode)
            ->assertSee($laporan->nama_pelapor);
    }

    public function test_petugas_bisa_mengubah_status_dan_catatan_penanganan(): void
    {
        $perumahan = $this->buatPerumahan();
        $laporan = Laporan::create($this->dataLaporan($perumahan) + ['kode' => Laporan::buatKode()]);
        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'selesai',
                'catatan_petugas' => 'PJU blok C sudah diperbaiki.',
            ])
            ->assertRedirect(route('laporan.show', $laporan));

        $laporan->refresh();

        $this->assertSame('selesai', $laporan->status);
        $this->assertSame('PJU blok C sudah diperbaiki.', $laporan->catatan_petugas);
        $this->assertSame($petugas->id, $laporan->ditangani_oleh);
        $this->assertNotNull($laporan->ditangani_pada);
    }

    public function test_hapus_perumahan_ikut_menghapus_laporannya(): void
    {
        $perumahan = $this->buatPerumahan();
        $laporan = Laporan::create($this->dataLaporan($perumahan) + ['kode' => Laporan::buatKode()]);

        $perumahan->delete();

        $this->assertDatabaseMissing('laporans', ['id' => $laporan->id]);
        $this->assertSame(0, Laporan::count());
    }

    public function test_publik_bisa_mengirim_pengaduan_teks_tanpa_perumahan_dan_foto(): void
    {
        $this->post(route('lapor.store'), $this->dataPengaduan());

        $laporan = Laporan::first();

        $this->assertNotNull($laporan);
        $this->assertSame('pengaduan', $laporan->jenis);
        $this->assertStringStartsWith('PGN-', $laporan->kode);
        $this->assertNull($laporan->perumahan_id);
        $this->assertNull($laporan->tingkat);
        $this->assertSame(0, $laporan->fotos()->count());
        $this->assertNull($laporan->persentase_kerusakan);
    }

    public function test_pengaduan_bisa_menyertakan_perumahan_dan_foto_opsional(): void
    {
        Storage::fake('public');
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataPengaduan([
            'perumahan_id' => $perumahan->id,
            'foto' => [UploadedFile::fake()->image('aduan.jpg')],
        ]));

        $laporan = Laporan::first();

        $this->assertSame('pengaduan', $laporan->jenis);
        $this->assertSame($perumahan->id, $laporan->perumahan_id);
        $this->assertCount(1, $laporan->fotos);
    }

    public function test_publik_bisa_melaporkan_bangunan_liar_dengan_kelurahan_dan_foto(): void
    {
        Storage::fake('public');
        $kelurahan = $this->buatKelurahan();

        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'kelurahan_id' => $kelurahan->id,
            'tanggal_kejadian' => '2026-10-01',
            'foto' => [UploadedFile::fake()->image('bukti-bangunan.jpg')],
        ]));

        $laporan = Laporan::first();

        $this->assertNotNull($laporan);
        $this->assertSame('bangunan_liar', $laporan->jenis);
        $this->assertStringStartsWith('BLR-', $laporan->kode);
        $this->assertNull($laporan->perumahan_id);
        $this->assertSame($kelurahan->id, $laporan->kelurahan_id);
        $this->assertSame('Jl. Kenanga gang 4, di samping warung Bu Yati', $laporan->lokasi_teks);
        $this->assertSame('2026-10-01', $laporan->tanggal_kejadian->format('Y-m-d'));
        $this->assertNull($laporan->tingkat);
        $this->assertCount(1, $laporan->fotos);
    }

    public function test_bangunan_liar_wajib_foto_dan_kelurahan(): void
    {
        $kelurahan = $this->buatKelurahan();

        // Tanpa foto -> ditolak.
        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'kelurahan_id' => $kelurahan->id,
        ]))->assertSessionHasErrors('foto');

        // Tanpa kelurahan -> ditolak.
        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'foto' => [UploadedFile::fake()->image('bukti.jpg')],
        ]))->assertSessionHasErrors('kelurahan_id');

        // Tanpa lokasi teks -> ditolak.
        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'kelurahan_id' => $kelurahan->id,
            'lokasi_teks' => null,
            'foto' => [UploadedFile::fake()->image('bukti.jpg')],
        ]))->assertSessionHasErrors('lokasi_teks');

        $this->assertSame(0, Laporan::count());
    }

    public function test_bangunan_liar_dengan_perumahan_mengikuti_kelurahan_perumahan(): void
    {
        Storage::fake('public');
        $perumahan = $this->buatPerumahan();
        $kelurahanLain = $this->buatKelurahan('Kelurahan Lain', 'Kecamatan Lain');

        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'perumahan_id' => $perumahan->id,
            'kelurahan_id' => $kelurahanLain->id,
            'foto' => [UploadedFile::fake()->image('bukti.jpg')],
        ]));

        $laporan = Laporan::first();

        $this->assertSame($perumahan->id, $laporan->perumahan_id);
        // Kelurahan mengikuti perumahan yang dipilih, bukan pilihan bebas pelapor.
        $this->assertSame($perumahan->kelurahan_id, $laporan->kelurahan_id);
    }

    public function test_tanggal_kejadian_tidak_boleh_masa_depan(): void
    {
        $kelurahan = $this->buatKelurahan();

        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'kelurahan_id' => $kelurahan->id,
            'tanggal_kejadian' => now()->addWeek()->toDateString(),
            'foto' => [UploadedFile::fake()->image('bukti.jpg')],
        ]))->assertSessionHasErrors('tanggal_kejadian');

        $this->assertSame(0, Laporan::count());
    }

    public function test_kerusakan_tetap_wajib_perumahan_dan_tingkat(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), [
            'jenis' => 'kerusakan',
            'nama_pelapor' => 'Budi',
            'kontak_pelapor' => '081234567890',
            'judul' => 'Jalan rusak',
            'deskripsi' => 'Berlubang.',
        ])->assertSessionHasErrors(['perumahan_id', 'tingkat']);

        $this->assertSame(0, Laporan::count());
    }

    public function test_jenis_laporan_tidak_valid_ditolak(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan, ['jenis' => 'jenis_ngawur']))
            ->assertSessionHasErrors('jenis');

        $this->assertSame(0, Laporan::count());
    }

    public function test_admin_bisa_filter_laporan_per_jenis(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->post(route('lapor.store'), $this->dataLaporan($perumahan));
        $this->post(route('lapor.store'), $this->dataPengaduan());
        $this->post(route('lapor.store'), $this->dataBangunanLiar([
            'kelurahan_id' => $this->buatKelurahan()->id,
            'foto' => [UploadedFile::fake()->image('bukti.jpg')],
        ]));

        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)
            ->get(route('laporan.index', ['jenis' => 'pengaduan']))
            ->assertOk()
            ->assertSee('Usulan penambahan tong sampah')
            ->assertDontSee('PJU blok C mati semua');

        $this->actingAs($petugas)
            ->get(route('laporan.index', ['jenis' => 'bangunan_liar']))
            ->assertOk()
            ->assertSee('Bangunan tambah tanpa izin di bantaran jalan');
    }
}
