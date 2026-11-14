<?php

namespace Tests\Feature\Konsol;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\Perumahan;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BersihkanYatimTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Storage::disk() dideklarasikan mengembalikan contract Filesystem, sedangkan
     * assertExists()/assertMissing() hanya ada di FilesystemAdapter. Helper ini
     * memberi tipe konkret agar IDE/static analysis tidak melaporkan false positive.
     */
    private function disk(string $nama): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($nama);

        return $disk;
    }

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

    private function fileLama(string $disk, string $path): void
    {
        $this->disk($disk)->put($path, 'foto-uji');
        touch($this->disk($disk)->path($path), now()->subHours(48)->timestamp);
    }

    public function test_simulasi_dan_hapus_menangani_file_yatim_pada_disk_yang_benar(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $laporan = $this->laporan();
        $perumahan = $this->perumahan();
        $dirujukLaporan = 'laporan/dirujuk.jpg';
        $dirujukPerumahan = 'perumahan/dirujuk.jpg';
        $this->disk('local')->put($dirujukLaporan, 'foto');
        $this->disk('public')->put($dirujukPerumahan, 'foto');
        $laporan->fotos()->create(['path' => $dirujukLaporan]);
        $perumahan->update(['foto_path' => $dirujukPerumahan]);

        $yatimLaporan = 'laporan/yatim.jpg';
        $yatimPerumahan = 'perumahan/yatim.jpg';
        $muda = 'perumahan/muda.jpg';
        $this->fileLama('local', $yatimLaporan);
        $this->fileLama('public', $yatimPerumahan);
        $this->disk('public')->put($muda, 'foto');

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 24]);
        $output = Artisan::output();
        $this->assertStringContainsString($yatimLaporan, $output);
        $this->assertStringContainsString($yatimPerumahan, $output);
        $this->assertStringNotContainsString($dirujukLaporan, $output);
        $this->assertStringNotContainsString($dirujukPerumahan, $output);
        $this->disk('local')->assertExists($yatimLaporan);
        $this->disk('public')->assertExists($yatimPerumahan);
        $this->disk('public')->assertExists($muda);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 24]);
        $this->disk('local')->assertMissing($yatimLaporan);
        $this->disk('public')->assertMissing($yatimPerumahan);
        $this->disk('public')->assertExists($muda);
        $this->disk('local')->assertExists($dirujukLaporan);
        $this->disk('public')->assertExists($dirujukPerumahan);
    }

    public function test_subfolder_perbaikan_di_disk_privat_menghormati_rujukan_dan_normalisasi_path(): void
    {
        Storage::fake('local');
        $laporan = $this->laporan();
        $dirujuk = 'laporan/perbaikan/dirujuk.jpg';
        $yatim = 'laporan/perbaikan/yatim.jpg';
        $this->disk('local')->put($dirujuk, 'foto');
        $laporan->fotos()->create(['path' => str_replace('/', DIRECTORY_SEPARATOR, $dirujuk), 'tipe' => LaporanFoto::TIPE_PERBAIKAN]);
        $this->fileLama('local', $yatim);

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 24]);
        $output = Artisan::output();
        $this->assertStringNotContainsString($dirujuk, $output);
        $this->assertStringContainsString($yatim, $output);
        $this->disk('local')->assertExists($dirujuk);
        $this->disk('local')->assertExists($yatim);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 24]);
        $this->disk('local')->assertExists($dirujuk);
        $this->disk('local')->assertMissing($yatim);
    }

    public function test_berkas_akar_dilaporkan_rujukan_hilang_dan_sisa_publik_tidak_dihapus(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->disk('public')->put('gagal.jpg', 'bukan gambar');
        $this->disk('local')->put('privat-aneh.jpg', 'bukan foto laporan');
        $laporan = $this->laporan();
        $pathHilang = 'laporan/tidak-ada.jpg';
        $laporan->fotos()->create(['path' => $pathHilang]);
        $yatim = 'laporan/yatim-umur-default.jpg';
        $this->fileLama('local', $yatim);
        $sisaPublik = 'laporan/sisa-lama.jpg';
        $this->disk('public')->put($sisaPublik, 'foto lama');

        Artisan::call('storage:bersihkan-yatim', ['--umur' => 'invalid']);
        $output = Artisan::output();
        $this->assertStringContainsString('gagal.jpg', $output);
        $this->assertStringContainsString('privat-aneh.jpg', $output);
        $this->assertStringContainsString('tak dikenal', $output);
        $this->assertStringContainsString($pathHilang, $output);
        $this->assertStringContainsString($yatim, $output);
        $this->assertStringContainsString($sisaPublik, $output);

        Artisan::call('storage:bersihkan-yatim', ['--hapus' => true, '--umur' => 'invalid']);
        $this->disk('public')->assertExists('gagal.jpg');
        $this->disk('local')->assertExists('privat-aneh.jpg');
        $this->disk('local')->assertMissing($yatim);
        $this->disk('public')->assertExists($sisaPublik);
        $this->assertDatabaseHas('laporan_fotos', ['path' => $pathHilang]);
    }
}
