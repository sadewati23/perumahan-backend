<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\Perumahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BersihkanYatimTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode(), 'jenis' => Laporan::JENIS_PENGADUAN,
            'judul' => 'Laporan Pembersihan', 'deskripsi' => 'Uji',
            'nama_pelapor' => 'Warga', 'status' => 'baru',
        ]);
    }

    private function perumahan(): Perumahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Foto '.uniqid()]);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Kelurahan Foto '.uniqid()]);

        return Perumahan::create([
            'nama' => 'Perumahan Foto', 'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000, 'jumlah_unit' => 10, 'status' => 'komersil',
        ]);
    }

    private function fileLama(string $path): void
    {
        Storage::disk('public')->put($path, 'foto-uji');
        touch(Storage::disk('public')->path($path), now()->subHours(48)->timestamp);
    }

    public function test_simulasi_dan_hapus_hanya_menangani_file_yatim_lama(): void
    {
        Storage::fake('public');
        $laporan = $this->laporan();
        $perumahan = $this->perumahan();
        $dirujukLaporan = 'laporan/dirujuk.jpg';
        $dirujukPerumahan = 'perumahan/dirujuk.jpg';
        Storage::disk('public')->put($dirujukLaporan, 'foto');
        Storage::disk('public')->put($dirujukPerumahan, 'foto');
        $laporan->fotos()->create(['path' => $dirujukLaporan]);
        $perumahan->update(['foto_path' => $dirujukPerumahan]);

        $yatim = 'laporan/yatim.jpg';
        $muda = 'perumahan/muda.jpg';
        $this->fileLama($yatim);
        Storage::disk('public')->put($muda, 'foto');

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 24]);
        $output = Artisan::output();
        $this->assertStringContainsString($yatim, $output);
        $this->assertStringNotContainsString($dirujukLaporan, $output);
        $this->assertStringNotContainsString($dirujukPerumahan, $output);
        Storage::disk('public')->assertExists($yatim);
        Storage::disk('public')->assertExists($muda);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 24]);
        Storage::disk('public')->assertMissing($yatim);
        Storage::disk('public')->assertExists($muda);
        Storage::disk('public')->assertExists($dirujukLaporan);
        Storage::disk('public')->assertExists($dirujukPerumahan);
    }

    public function test_subfolder_perbaikan_menghormati_rujukan_dan_normalisasi_path(): void
    {
        Storage::fake('public');
        $laporan = $this->laporan();
        $dirujuk = 'laporan/perbaikan/dirujuk.jpg';
        $yatim = 'laporan/perbaikan/yatim.jpg';
        Storage::disk('public')->put($dirujuk, 'foto');
        $laporan->fotos()->create(['path' => str_replace('/', DIRECTORY_SEPARATOR, $dirujuk), 'tipe' => LaporanFoto::TIPE_PERBAIKAN]);
        $this->fileLama($yatim);

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 24]);
        $output = Artisan::output();
        $this->assertStringNotContainsString($dirujuk, $output);
        $this->assertStringContainsString($yatim, $output);
        Storage::disk('public')->assertExists($dirujuk);
        Storage::disk('public')->assertExists($yatim);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 24]);
        Storage::disk('public')->assertExists($dirujuk);
        Storage::disk('public')->assertMissing($yatim);
    }

    public function test_berkas_akar_hanya_dilaporkan_rujukan_hilang_dan_umur_invalid(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('gagal.jpg', 'bukan gambar');
        $laporan = $this->laporan();
        $pathHilang = 'laporan/tidak-ada.jpg';
        $laporan->fotos()->create(['path' => $pathHilang]);
        $yatim = 'laporan/yatim-umur-default.jpg';
        $this->fileLama($yatim);

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 'invalid']);
        $output = Artisan::output();
        $this->assertStringContainsString('gagal.jpg', $output);
        $this->assertStringContainsString('tak dikenal', $output);
        $this->assertStringContainsString($pathHilang, $output);
        $this->assertStringContainsString($yatim, $output);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 'invalid']);
        Storage::disk('public')->assertExists('gagal.jpg');
        Storage::disk('public')->assertMissing($yatim);
        $this->assertDatabaseHas('laporan_fotos', ['path' => $pathHilang]);
    }
}
