<?php

namespace Tests\Feature\Admin;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoPrivatTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(string $judul = 'Laporan Foto Privat'): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode(),
            'jenis' => Laporan::JENIS_PENGADUAN,
            'nama_pelapor' => 'Warga Uji',
            'judul' => $judul,
            'deskripsi' => 'Uraian laporan uji foto privat.',
            'status' => 'baru',
        ]);
    }

    private function foto(Laporan $laporan, string $path = 'laporan/uji.jpg'): LaporanFoto
    {
        Storage::disk('local')->put($path, 'isi-foto-uji');

        return LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => $path,
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);
    }

    private function pengguna(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_tamu_tidak_bisa_membuka_foto_laporan(): void
    {
        Storage::fake('local');
        $laporan = $this->laporan();
        $foto = $this->foto($laporan);

        $this->get(route('laporan.foto', ['laporan' => $laporan, 'foto' => $foto]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_dan_petugas_bisa_membuka_foto_dengan_header_privat(): void
    {
        Storage::fake('local');
        $laporan = $this->laporan();
        $foto = $this->foto($laporan);
        $url = route('laporan.foto', ['laporan' => $laporan, 'foto' => $foto]);

        foreach (['admin', 'petugas'] as $role) {
            $response = $this->actingAs($this->pengguna($role))->get($url)
                ->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');

            $cache = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('private', $cache);
            $this->assertStringContainsString('no-store', $cache);
        }
    }

    public function test_foto_milik_laporan_lain_tidak_bisa_dibuka_lewat_id_laporan_lain(): void
    {
        Storage::fake('local');
        $laporan = $this->laporan('Laporan Pertama');
        $laporanLain = $this->laporan('Laporan Kedua');
        $fotoLain = $this->foto($laporanLain, 'laporan/milik-lain.jpg');

        $this->actingAs($this->pengguna('admin'))
            ->get(route('laporan.foto', ['laporan' => $laporan, 'foto' => $fotoLain]))
            ->assertStatus(404);

        // Foto yang tidak punya file juga tidak boleh membocorkan apa pun.
        $fotoTanpaFile = LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => 'laporan/tidak-ada.jpg',
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);

        $this->actingAs($this->pengguna('admin'))
            ->get(route('laporan.foto', ['laporan' => $laporan, 'foto' => $fotoTanpaFile]))
            ->assertStatus(404);
    }

    public function test_simulasi_pemindahan_foto_tidak_mengubah_apa_pun(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $laporan = $this->laporan();
        $path = 'laporan/lama-di-publik.jpg';
        Storage::disk('public')->put($path, 'foto-lama');
        $foto = LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => $path,
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);

        Artisan::call('foto:pindahkan-ke-privat');
        $output = Artisan::output();

        $this->assertStringContainsString('Simulasi', $output);
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame($path, $foto->fresh()->path);

        // Eksekusi nyata memindahkan file dan tetap mempertahankan path DB.
        Artisan::call('foto:pindahkan-ke-privat', ['--jalankan' => true]);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame($path, $foto->fresh()->path);
    }
}
