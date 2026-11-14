<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PerumahanImportDuplikatTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
    }

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('perumahan.csv', $isi);
    }

    private function header(): string
    {
        return "Nama Perumahan,Kecamatan,Kelurahan,Luas Lahan,Jumlah Unit\n";
    }

    private function pesanFile(): string
    {
        return implode(' ', collect(session('errors')->getBag('default')->get('file'))->all());
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

    public function test_impor_menolak_duplikat_terhadap_database(): void
    {
        $kelurahan = $this->wilayah();
        $this->buat($kelurahan, 'Perumahan Amanah');

        $isi = $this->header()
            ."Perumahan Baru,Coblong,Dago,12000,50\n"
            ."perumahan  amanah,Coblong,Dago,8000,30\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        // Hanya data lama yang tersisa; baris valid lain ikut batal (all-or-nothing).
        $this->assertSame(1, Perumahan::count());
        $this->assertDatabaseHas('perumahans', ['nama' => 'Perumahan Amanah']);

        $pesan = $this->pesanFile();
        $this->assertStringContainsString('Baris 3', $pesan);
        $this->assertStringContainsString('sudah terdaftar', $pesan);
        $this->assertStringContainsString('Perumahan Amanah', $pesan);
    }

    public function test_impor_menolak_duplikat_antar_baris_dalam_file_yang_sama(): void
    {
        $this->wilayah();

        $isi = $this->header()
            ."Perumahan Kembar,Coblong,Dago,5000,10\n"
            ."PERUMahan  kembar,Coblong,Dago,6000,12\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $this->assertSame(0, Perumahan::count());

        $pesan = $this->pesanFile();
        $this->assertStringContainsString('Baris 3', $pesan);
        $this->assertStringContainsString('baris 2', $pesan);
        $this->assertStringContainsString('TIDAK disimpan', $pesan);
    }

    public function test_tidak_ada_data_tersimpan_bila_ada_satu_baris_duplikat_dalam_file(): void
    {
        $this->wilayah();

        $isi = $this->header()
            ."Perumahan Bagus,Coblong,Dago,5000,10\n"
            ."Perumahan Bagus,Coblong,Dago,7000,20\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $this->assertSame(0, Perumahan::count());
        $this->assertStringContainsString('Baris 3', $this->pesanFile());
    }

    public function test_file_bersih_tetap_berhasil_dan_nama_dinormalisasi(): void
    {
        $this->wilayah();

        $isi = $this->header()
            ."  Perumahan   Rapi  ,Coblong,Dago,5000,10\n"
            ."Perumahan Lain,Coblong,Dago,7000,20\n";

        $response = $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $response->assertRedirect(route('perumahan.index'));
        $this->assertSame(2, Perumahan::count());
        $this->assertDatabaseHas('perumahans', ['nama' => 'Perumahan Rapi']);
        $this->assertDatabaseHas('perumahans', ['nama' => 'Perumahan Lain']);
    }
}
