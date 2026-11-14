<?php

namespace Tests\Feature\Konsol;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CekDuplikatPerumahanTest extends TestCase
{
    use RefreshDatabase;

    private function wilayah(string $nama): Kelurahan
    {
        $kecamatan = Kecamatan::firstOrCreate(
            ['nama' => 'Coblong'],
            ['kabupaten_kota' => 'Kota Bandung'],
        );

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => $nama]);
    }

    private function buat(Kelurahan $kelurahan, string $nama): Perumahan
    {
        return Perumahan::create([
            'nama' => $nama,
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
        ]);
    }

    public function test_command_melaporkan_duplikat_dan_tidak_mengubah_data(): void
    {
        $dago = $this->wilayah('Dago');
        $sekitar = $this->wilayah('Sekeloa');

        $pertama = $this->buat($dago, 'Perumahan Dago Indah');
        // Varian beda kapitalisasi & spasi ganda pada kelurahan yang sama.
        $kedua = $this->buat($dago, 'perumahan  dago indah');
        // Nama sama tetapi kelurahan berbeda: bukan duplikat.
        $ketiga = $this->buat($sekitar, 'Perumahan Dago Indah');

        Laporan::create([
            'kode' => 'PGN-TES-0001',
            'jenis' => 'pengaduan',
            'perumahan_id' => $pertama->id,
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Uji laporan',
            'deskripsi' => 'Deskripsi uji.',
            'status' => 'baru',
        ]);

        $kodeKeluar = Artisan::call('perumahan:cek-duplikat');
        $keluaran = Artisan::output();

        $this->assertSame(1, $kodeKeluar);
        $this->assertStringContainsString('duplikat', $keluaran);
        $this->assertStringContainsString('Perumahan Dago Indah', $keluaran);
        $this->assertStringContainsString('perumahan  dago indah', $keluaran);
        $this->assertStringContainsString('Dago', $keluaran);
        $this->assertStringContainsString((string) $pertama->id, $keluaran);
        $this->assertStringContainsString((string) $kedua->id, $keluaran);
        $this->assertStringNotContainsString((string) $ketiga->id, $keluaran);

        // Baca-saja: seluruh baris tetap utuh.
        $this->assertSame(3, Perumahan::count());
        $this->assertDatabaseHas('perumahans', ['id' => $pertama->id, 'nama' => 'Perumahan Dago Indah']);
        $this->assertDatabaseHas('perumahans', ['id' => $kedua->id, 'nama' => 'perumahan  dago indah']);
        $this->assertSame(1, Laporan::where('perumahan_id', $pertama->id)->count());
    }

    public function test_command_selesai_normal_bila_tidak_ada_duplikat(): void
    {
        $dago = $this->wilayah('Dago');
        $this->buat($dago, 'Perumahan Satu');
        $this->buat($dago, 'Perumahan Dua');

        $kodeKeluar = Artisan::call('perumahan:cek-duplikat');

        $this->assertSame(0, $kodeKeluar);
        $this->assertStringContainsString('Tidak ada duplikat', Artisan::output());
        $this->assertSame(2, Perumahan::count());
    }
}
