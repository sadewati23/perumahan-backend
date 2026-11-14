<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HakAksesHapusTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function wilayah(string $suffix = ''): array
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kec '.$suffix.uniqid()]);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Kel '.$suffix.uniqid()]);

        return [$kecamatan, $kelurahan];
    }

    private function perumahan(string $nama = 'Uji'): Perumahan
    {
        [, $kelurahan] = $this->wilayah($nama);

        return Perumahan::create([
            'nama' => $nama,
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
        ]);
    }

    private function laporan(?int $perumahanId = null): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode(),
            'jenis' => Laporan::JENIS_PENGADUAN,
            'perumahan_id' => $perumahanId,
            'judul' => 'Laporan uji',
            'deskripsi' => 'Deskripsi uji',
            'nama_pelapor' => 'Warga',
            'status' => 'baru',
        ]);
    }

    public function test_petugas_tidak_bisa_menghapus_data_dan_admin_bisa(): void
    {
        $petugas = $this->petugas();
        $admin = $this->admin();

        $perumahan = $this->perumahan('P');
        $this->actingAs($petugas)->delete(route('perumahan.destroy', $perumahan))->assertForbidden();
        $this->assertDatabaseHas('perumahans', ['id' => $perumahan->id]);
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->delete(route('perumahan.destroy', $perumahan))->assertRedirect();

        $laporan = $this->laporan();
        $this->actingAs($petugas)->delete(route('laporan.destroy', $laporan))->assertForbidden();
        $this->assertDatabaseHas('laporans', ['id' => $laporan->id]);
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->delete(route('laporan.destroy', $laporan))->assertRedirect();

        [$kecamatan] = $this->wilayah('K');
        $this->actingAs($petugas)->delete(route('kecamatan.destroy', $kecamatan))->assertForbidden();
        $this->assertDatabaseHas('kecamatans', ['id' => $kecamatan->id]);
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->delete(route('kecamatan.destroy', $kecamatan))->assertRedirect();

        [, $kelurahan] = $this->wilayah('L');
        $this->actingAs($petugas)->delete(route('kelurahan.destroy', $kelurahan))->assertForbidden();
        $this->assertDatabaseHas('kelurahans', ['id' => $kelurahan->id]);
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->delete(route('kelurahan.destroy', $kelurahan))->assertRedirect();
    }

    public function test_penghapusan_foto_perbaikan_mengikuti_pengunggah_atau_admin(): void
    {
        Storage::fake('local');
        $petugas = $this->petugas();
        $orangLain = $this->petugas();
        $admin = $this->admin();
        $laporan = $this->laporan();
        $milikPetugas = LaporanFoto::create(['laporan_id' => $laporan->id, 'path' => 'milik-petugas.jpg', 'tipe' => LaporanFoto::TIPE_PERBAIKAN, 'diunggah_oleh' => $petugas->id]);
        $milikOrangLain = LaporanFoto::create(['laporan_id' => $laporan->id, 'path' => 'milik-orang-lain.jpg', 'tipe' => LaporanFoto::TIPE_PERBAIKAN, 'diunggah_oleh' => $orangLain->id]);

        $this->actingAs($petugas)->delete(route('laporan.foto-perbaikan.destroy', [$laporan, $milikPetugas]))->assertRedirect();
        $this->assertDatabaseMissing('laporan_fotos', ['id' => $milikPetugas->id]);
        $this->actingAs($petugas)->delete(route('laporan.foto-perbaikan.destroy', [$laporan, $milikOrangLain]))->assertForbidden();
        $this->assertDatabaseHas('laporan_fotos', ['id' => $milikOrangLain->id]);
        $this->actingAs($admin)->delete(route('laporan.foto-perbaikan.destroy', [$laporan, $milikOrangLain]))->assertRedirect();
    }

    public function test_tombol_hapus_perumahan_hanya_tampil_bagi_admin(): void
    {
        $perumahan = $this->perumahan('Tombol');
        $url = route('perumahan.destroy', $perumahan);
        $this->actingAs($this->petugas())->get(route('perumahan.index'))->assertDontSee('action="'.$url.'"', false);
        $this->actingAs($this->admin())->get(route('perumahan.index'))->assertSee('action="'.$url.'"', false);
    }

    public function test_hapus_perumahan_membersihkan_foto_laporan_miliknya_saja(): void
    {
        Storage::fake('local');
        $milik = $this->perumahan('Milik');
        $lain = $this->perumahan('Lain');
        $fotoMilik = 'laporan/milik.jpg';
        $fotoLain = 'laporan/lain.jpg';
        Storage::disk('local')->put($fotoMilik, 'foto');
        Storage::disk('local')->put($fotoLain, 'foto');
        $this->laporan($milik->id)->fotos()->create(['path' => $fotoMilik]);
        $this->laporan($lain->id)->fotos()->create(['path' => $fotoLain]);

        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($this->admin())->delete(route('perumahan.destroy', $milik))->assertRedirect();
        Storage::disk('local')->assertMissing($fotoMilik);
        Storage::disk('local')->assertExists($fotoLain);
    }
}
