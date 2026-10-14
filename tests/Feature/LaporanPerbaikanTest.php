<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanPerbaikanTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function buatLaporan(array $overrides = []): Laporan
    {
        return Laporan::create($overrides + [
            'kode' => Laporan::buatKode($overrides['jenis'] ?? Laporan::JENIS_PENGADUAN),
            'jenis' => Laporan::JENIS_PENGADUAN,
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Judul laporan uji perbaikan',
            'deskripsi' => 'Uraian laporan uji fitur perbaikan.',
            'status' => 'baru',
        ]);
    }

    private function fileFoto(string $nama = 'perbaikan.jpg', int $lebar = 60, int $tinggi = 60): UploadedFile
    {
        return UploadedFile::fake()->image($nama, $lebar, $tinggi);
    }

    private function tandaiBuktiPelapor(Laporan $laporan, string $path = 'laporan/bukti-warga.jpg'): LaporanFoto
    {
        return LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => $path,
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);
    }

    /** @test */
    public function petugas_bisa_upload_foto_perbaikan_dan_tercatat_tepat(): void
    {
        Storage::fake('public');
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan();
        $this->tandaiBuktiPelapor($laporan);

        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto()],
                'keterangan' => 'PJU blok C diganti tegangan baru',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $foto = LaporanFoto::query()->where('tipe', LaporanFoto::TIPE_PERBAIKAN)->firstOrFail();

        $this->assertSame($laporan->id, $foto->laporan_id);
        $this->assertSame($petugas->id, $foto->diunggah_oleh);
        $this->assertSame('PJU blok C diganti tegangan baru', $foto->keterangan);
        $this->assertStringStartsWith('laporan/perbaikan/', $foto->path);
        $this->assertTrue(Storage::disk('public')->exists($foto->path));

        // Foto perbaikan TIDAK masuk kartu foto bukti warga.
        $laporan->refresh();
        $this->assertSame(1, $laporan->fotosPerbaikan()->count());
        $this->assertSame(1, $laporan->fotosLaporan()->count());
    }

    /** @test */
    public function tamu_dan_akun_nonaktif_ditolak(): void
    {
        Storage::fake('public');
        $laporan = $this->buatLaporan();

        // Tamu diarahkan ke login.
        $this->post(route('laporan.foto-perbaikan.store', $laporan), [
            'foto_perbaikan' => [$this->fileFoto()],
        ])->assertRedirect(route('login'));
        $this->assertSame(0, LaporanFoto::count());

        // Akun nonaktif: middleware 'active' mem-logout dan menolak.
        $nonaktif = User::factory()->create(['role' => 'petugas', 'is_active' => false]);
        $this->actingAs($nonaktif)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto()],
            ])->assertRedirect(route('login'));
        $this->assertSame(0, LaporanFoto::count());
    }

    /** @test */
    public function status_ditolak_menolak_upload(): void
    {
        Storage::fake('public');
        $laporan = $this->buatLaporan(['status' => 'ditolak']);

        $this->actingAs($this->petugas())
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto()],
            ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, LaporanFoto::count());
    }

    /** @test */
    public function file_non_gambar_lebih_dari_5_dan_lebih_dari_10_ditolak(): void
    {
        Storage::fake('public');
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan();

        // 1) File bukan gambar.
        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [
                    UploadedFile::fake()->createWithContent('bukan-gambar.txt', 'teks polos'),
                ],
            ])->assertSessionHasErrors('foto_perbaikan.0');
        $this->assertSame(0, LaporanFoto::count());

        // 2) Lebih dari 5 foto dalam satu unggahan.
        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [
                    $this->fileFoto('a1.jpg'), $this->fileFoto('a2.jpg'), $this->fileFoto('a3.jpg'),
                    $this->fileFoto('a4.jpg'), $this->fileFoto('a5.jpg'), $this->fileFoto('a6.jpg'),
                ],
            ])->assertSessionHasErrors('foto_perbaikan');
        $this->assertSame(0, LaporanFoto::count());

        // 3) Total foto perbaikan melebihi batas 10 per laporan.
        for ($i = 1; $i <= 10; $i++) {
            LaporanFoto::create([
                'laporan_id' => $laporan->id,
                'path' => "laporan/perbaikan/lama-{$i}.jpg",
                'tipe' => LaporanFoto::TIPE_PERBAIKAN,
                'diunggah_oleh' => $petugas->id,
            ]);
        }

        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto('tambahan.jpg')],
            ])->assertSessionHas('error');

        $this->assertSame(10, LaporanFoto::query()->where('tipe', LaporanFoto::TIPE_PERBAIKAN)->count());
    }

    /** @test */
    public function hapus_foto_perbaikan_dan_guard_idor_bekerja(): void
    {
        Storage::fake('public');
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan();

        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto()],
            ])->assertRedirect();

        $foto = LaporanFoto::query()
            ->where('tipe', LaporanFoto::TIPE_PERBAIKAN)
            ->where('laporan_id', $laporan->id)
            ->firstOrFail();
        $path = $foto->path;
        $this->assertTrue(Storage::disk('public')->exists($path));

        // Hapus foto perbaikan menghapus file + baris.
        $this->actingAs($petugas)
            ->delete(route('laporan.foto-perbaikan.destroy', [$laporan, $foto]))
            ->assertRedirect();

        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertNull(LaporanFoto::find($foto->id));

        // FOTO BUKTI WARGA dilindungi: rute hapus menolak dengan 404.
        $buktiWarga = $this->tandaiBuktiPelapor($laporan);
        Storage::disk('public')->put($buktiWarga->path, 'data-warga');

        $this->actingAs($petugas)
            ->delete(route('laporan.foto-perbaikan.destroy', [$laporan, $buktiWarga]))
            ->assertStatus(404); // abort(404) di controller: cegah IDOR & hapus foto bukti warga
        $this->assertTrue(Storage::disk('public')->exists($buktiWarga->path));
        $this->assertNotNull(LaporanFoto::find($buktiWarga->id));

        // Foto milik LAPORAN LAIN juga ditolak 404.
        $laporanLain = $this->buatLaporan([
            'judul' => 'Laporan milik pengguna lain',
        ]);
        LaporanFoto::create([
            'laporan_id' => $laporanLain->id,
            'path' => 'laporan/perbaikan/milik-lain.jpg',
            'tipe' => LaporanFoto::TIPE_PERBAIKAN,
        ]);

        $this->actingAs($petugas)
            ->delete(route('laporan.foto-perbaikan.destroy', [$laporan, LaporanFoto::latest('id')->first()]))
            ->assertStatus(404);
        $this->assertTrue(LaporanFoto::where('laporan_id', $laporanLain->id)->exists());
    }

    /** @test */
    public function status_selesai_mengisi_selesai_pada_dan_deskripsi_aksi(): void
    {
        $laporan = $this->buatLaporan();
        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'selesai',
                'catatan_petugas' => 'Tim lapangan turun hari ini.',
                'deskripsi_aksi_selesai' => 'Penggantian PJU blok C selesai dilakukan.',
            ])
            ->assertRedirect();

        $laporan->refresh();
        $this->assertSame('selesai', $laporan->status);
        $this->assertSame('Penggantian PJU blok C selesai dilakukan.', $laporan->deskripsi_aksi_selesai);
        $this->assertNotNull($laporan->selesai_pada);

        $tanggalSelesai = $laporan->selesai_pada;

        // Kembali ke 'diproses' → selesai_pada dikosongkan.
        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'diproses',
            ])
            ->assertRedirect();

        $laporan->refresh();
        $this->assertSame('diproses', $laporan->status);
        $this->assertNull($laporan->selesai_pada);

        // Ditandai selesai kembali: timestamp diperbarui, bukan menetap nilai lama.
        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'selesai',
            ])
            ->assertRedirect();
        $this->assertNotNull($laporan->fresh()->selesai_pada);
    }

    /** @test */
    public function halaman_cetak_benar(): void
    {
        Storage::fake('public');
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan([
            'nama_pelapor' => "<script>alert(1)</script>",
            'kontak_pelapor' => '081234567890',
            'status' => 'selesai',
        ]);

        $perbaikan = LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => 'laporan/perbaikan/hasil.jpg',
            'tipe' => LaporanFoto::TIPE_PERBAIKAN,
            'keterangan' => 'Keterangan hasil perbaikan uji',
            'diunggah_oleh' => $petugas->id,
        ]);

        $this->actingAs($petugas)
            ->get(route('laporan.cetak', $laporan))
            ->assertOk()
            ->assertSee($laporan->kode)
            ->assertSee($laporan->judul)
            ->assertSee('Keterangan hasil perbaikan uji')
            // Kontak pelapor sengaja TIDAK dicetak.
            ->assertDontSee('081234567890')
            // Nama pelapor berbahaya harus ter-escape di HTML.
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            // Daftar foto bukti milik foto tipe 'perbaikan'.
            ->assertSee('Foto Kondisi Awal')
            ->assertSee('Foto Hasil Perbaikan');
    }

    /** @test */
    public function tamu_tidak_bisa_membuka_cetak(): void
    {
        $laporan = $this->buatLaporan();

        $this->get(route('laporan.cetak', $laporan))->assertRedirect(route('login'));
        $this->get(route('laporan.cetak-rekap'))->assertRedirect(route('login'));
    }

    /** @test */
    public function cetak_rekap_tersedia_dan_menghormati_filter_status(): void
    {
        $baru = $this->buatLaporan(['status' => 'baru', 'kode' => Laporan::buatKode()]);
        $selesai = $this->buatLaporan([
            'status' => 'selesai',
            'kode' => Laporan::buatKode(),
            'selesai_pada' => now(),
        ]);

        $petugas = $this->petugas();
        $this->actingAs($petugas)
            ->get(route('laporan.cetak-rekap'))
            ->assertOk()
            ->assertSee($baru->kode)
            ->assertSee($selesai->kode);

        $this->actingAs($petugas)
            ->get(route('laporan.cetak-rekap', ['status' => 'baru']))
            ->assertOk()
            ->assertSee($baru->kode)
            ->assertDontSee($selesai->kode);
    }

    /** @test */
    public function hapus_laporan_ikut_menghapus_foto_perbaikan(): void
    {
        Storage::fake('public');
        $petugas = $this->petugas();
        $laporan = $this->buatLaporan();

        $this->actingAs($petugas)
            ->post(route('laporan.foto-perbaikan.store', $laporan), [
                'foto_perbaikan' => [$this->fileFoto()],
            ])->assertRedirect();

        $foto = LaporanFoto::query()->where('tipe', LaporanFoto::TIPE_PERBAIKAN)->firstOrFail();
        $buktiWarga = $this->tandaiBuktiPelapor($laporan);
        Storage::disk('public')->put($buktiWarga->path, 'data-warga');

        $this->assertTrue(Storage::disk('public')->exists($foto->path));

        $this->actingAs($petugas)
            ->delete(route('laporan.destroy', $laporan))
            ->assertRedirect(route('laporan.index'));

        $this->assertFalse(Storage::disk('public')->exists($foto->path));
        $this->assertFalse(Storage::disk('public')->exists($buktiWarga->path));
        $this->assertSame(0, LaporanFoto::count());
        $this->assertNull(Laporan::find($laporan->id));
    }
}
