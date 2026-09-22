<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublikTest extends TestCase
{
    use RefreshDatabase;

    private function buatPerumahan(array $atribut = []): Perumahan
    {
        $kecamatan = Kecamatan::firstOrCreate(['nama' => 'Coblong'], ['kabupaten_kota' => 'Kota Bandung']);
        $kelurahan = Kelurahan::firstOrCreate(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);

        $perumahan = Perumahan::create(array_merge([
            'nama' => 'Perumahan Contoh',
            'pengembang' => 'PT Contoh',
            'kelurahan_id' => $kelurahan->id,
            'alamat' => 'Jl. Contoh No. 1',
            'rt' => '001',
            'rw' => '002',
            'luas_lahan' => 10000,
            'jumlah_unit' => 80,
            'status' => 'komersil',
            'tahun_pembuatan' => 2020,
            'tahun_selesai' => 2022,
            'jumlah_rth' => 2,
            'luas_rth' => 1500,
            'kdb' => 55,
            'kdh' => 35,
            'latitude' => -6.9,
            'longitude' => 107.6,
        ], $atribut));

        $perumahan->psu()->create(['jenis' => 'Jalan Lingkungan', 'jumlah' => 900, 'satuan' => 'meter', 'kondisi' => 'baik']);
        $perumahan->tipeUnits()->create(['tipe' => 'Tipe 36', 'jumlah_unit' => 80, 'luas_tanah' => 72, 'luas_bangunan' => 36, 'harga' => 385000000]);

        return $perumahan;
    }

    public function test_beranda_publik_menampilkan_statistik_dan_daftar(): void
    {
        $perumahan = $this->buatPerumahan();

        $this->get(route('publik.beranda'))
            ->assertOk()
            ->assertSee('Informasi Data Perumahan')
            ->assertSee($perumahan->nama)
            ->assertSee('Perumahan Terdata');
    }

    public function test_daftar_perumahan_publik_bisa_difilter(): void
    {
        $perumahan = $this->buatPerumahan();
        $subsidi = $this->buatPerumahan(['nama' => 'Perumahan Subsidi Uji', 'status' => 'subsidi']);

        $this->get(route('publik.perumahan.index'))
            ->assertOk()
            ->assertSee($perumahan->nama)
            ->assertSee($subsidi->nama);

        $this->get(route('publik.perumahan.index', ['status' => 'subsidi']))
            ->assertOk()
            ->assertSee($subsidi->nama)
            ->assertDontSee($perumahan->nama);

        $this->get(route('publik.perumahan.index', ['q' => 'tidak-ada-hasil']))
            ->assertOk()
            ->assertSee('Tidak ada perumahan yang cocok');
    }

    public function test_detail_publik_menampilkan_psu_tipe_unit_dan_pemakaman(): void
    {
        $perumahan = $this->buatPerumahan([
            'ada_lahan_pemakaman' => true,
            'luas_lahan_pemakaman' => 1200,
            'lokasi_lahan_pemakaman' => 'Blok D',
            'keterangan' => 'Akses jalan 8 meter.',
        ]);

        $this->get(route('publik.perumahan.show', $perumahan))
            ->assertOk()
            ->assertSee($perumahan->nama)
            ->assertSee('Tipe 36')
            ->assertSee('Jalan Lingkungan')
            ->assertSee('Tersedia')
            ->assertSee('Akses jalan 8 meter.');
    }

    public function test_peta_publik_dan_geojson_dapat_diakses(): void
    {
        $this->buatPerumahan();

        $this->get(route('publik.peta'))->assertOk()->assertSee('Peta Sebaran Perumahan');

        $this->get(route('publik.peta.geojson'))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.nama', 'Perumahan Contoh');
    }

    public function test_perumahan_yang_dihapus_tidak_tampil_di_publik(): void
    {
        $perumahan = $this->buatPerumahan(['nama' => 'Perumahan Dihapus']);
        $perumahan->delete();

        $this->get(route('publik.perumahan.index'))->assertOk()->assertDontSee('Perumahan Dihapus');
        $this->get(route('publik.perumahan.show', $perumahan->id))->assertNotFound();
    }
}
