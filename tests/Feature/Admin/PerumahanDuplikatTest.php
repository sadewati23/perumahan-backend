<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerumahanDuplikatTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function wilayah(string $kelurahan = 'Dago'): Kelurahan
    {
        $kecamatan = Kecamatan::firstOrCreate(
            ['nama' => 'Coblong'],
            ['kabupaten_kota' => 'Kota Bandung'],
        );

        return Kelurahan::firstOrCreate(
            ['kecamatan_id' => $kecamatan->id, 'nama' => $kelurahan],
        );
    }

    private function payload(Kelurahan $kelurahan, string $nama): array
    {
        return [
            'nama' => $nama,
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ];
    }

    private function buat(Kelurahan $kelurahan, string $nama): Perumahan
    {
        return Perumahan::create($this->payload($kelurahan, $nama));
    }

    private function pesanErrorNama(): string
    {
        return (string) session('errors')->getBag('default')->first('nama');
    }

    public function test_tambah_perumahan_duplikat_ditolak(): void
    {
        $kelurahan = $this->wilayah();
        $this->buat($kelurahan, 'Perumahan Dago Indah');

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), $this->payload($kelurahan, 'Perumahan Dago Indah'))
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Perumahan::count());

        $pesan = $this->pesanErrorNama();
        $this->assertStringContainsString('Perumahan Dago Indah', $pesan);
        $this->assertStringContainsString('Dago', $pesan);
    }

    public function test_nama_sama_pada_kelurahan_berbeda_diizinkan(): void
    {
        $dago = $this->wilayah('Dago');
        $sekitar = $this->wilayah('Sekeloa');
        $this->buat($dago, 'Perumahan Sama');

        $response = $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), $this->payload($sekitar, 'Perumahan Sama'));

        $baru = Perumahan::where('kelurahan_id', $sekitar->id)->first();

        $this->assertNotNull($baru);
        $response->assertRedirect(route('perumahan.show', $baru));
        $this->assertSame(2, Perumahan::count());
        $this->assertSame('Perumahan Sama', $baru->nama);
    }

    public function test_beda_huruf_besar_kecil_dan_spasi_ganda_tetap_dianggap_duplikat(): void
    {
        $kelurahan = $this->wilayah();
        $this->buat($kelurahan, 'Perumahan Dago Indah');

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), $this->payload($kelurahan, '  perumahan   DAGO indah  '))
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Perumahan::count());
        $this->assertStringContainsString('Perumahan Dago Indah', $this->pesanErrorNama());
    }

    public function test_ubah_tanpa_mengganti_nama_tidak_dianggap_duplikat_dirinya_sendiri(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = $this->buat($kelurahan, 'Perumahan Lama');

        $this->actingAs($this->petugas())
            ->put(route('perumahan.update', $perumahan), $this->payload($kelurahan, 'Perumahan Lama'))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('perumahan.show', $perumahan));

        $this->assertSame(1, Perumahan::count());
        $this->assertSame('Perumahan Lama', $perumahan->fresh()->nama);
    }

    public function test_ubah_menjadi_nama_perumahan_lain_yang_sudah_ada_ditolak(): void
    {
        $kelurahan = $this->wilayah();
        $this->buat($kelurahan, 'Perumahan Satu');
        $kedua = $this->buat($kelurahan, 'Perumahan Dua');

        $this->actingAs($this->petugas())
            ->put(route('perumahan.update', $kedua), $this->payload($kelurahan, '  perumahan   satu '))
            ->assertSessionHasErrors('nama');

        $this->assertSame('Perumahan Dua', $kedua->fresh()->nama);
        $this->assertSame(2, Perumahan::count());
    }
}
