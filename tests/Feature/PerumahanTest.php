<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerumahanTest extends TestCase
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

    public function test_tamu_tidak_bisa_membuka_data_perumahan(): void
    {
        $this->get(route('perumahan.index'))->assertRedirect(route('login'));
    }

    public function test_petugas_bisa_menyimpan_perumahan_beserta_psu_dan_tipe_unit(): void
    {
        $kelurahan = $this->wilayah();

        $response = $this->actingAs($this->petugas())->post(route('perumahan.store'), [
            'nama' => 'Perumahan Uji Coba',
            'pengembang' => 'PT Uji',
            'kelurahan_id' => $kelurahan->id,
            'alamat' => 'Jl. Uji No. 1',
            'rt' => '001',
            'rw' => '002',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'luas_lahan' => 20000,
            'jumlah_unit' => 100,
            'status' => 'subsidi',
            'tahun_pembuatan' => 2020,
            'tahun_selesai' => 2022,
            'jumlah_rth' => 2,
            'luas_rth' => 2000,
            'kdb' => 60,
            'kdh' => 30,
            'ada_lahan_pemakaman' => 1,
            'luas_lahan_pemakaman' => 1000,
            'lokasi_lahan_pemakaman' => 'Blok A',
            'psu' => [
                ['jenis' => 'Jalan Lingkungan', 'jumlah' => 1500, 'satuan' => 'meter', 'kondisi' => 'baik'],
            ],
            'tipe_units' => [
                ['tipe' => 'Tipe 30', 'jumlah_unit' => 60, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                ['tipe' => 'Tipe 36', 'jumlah_unit' => 40, 'luas_tanah' => 72, 'luas_bangunan' => 36],
            ],
        ]);

        $perumahan = Perumahan::first();

        $response->assertRedirect(route('perumahan.show', $perumahan));
        $this->assertSame('Perumahan Uji Coba', $perumahan->nama);
        $this->assertSame('subsidi', $perumahan->status);
        $this->assertTrue($perumahan->ada_lahan_pemakaman);
        $this->assertCount(1, $perumahan->psu);
        $this->assertCount(2, $perumahan->tipeUnits);
        $this->assertSame(100, $perumahan->total_unit_tipe);
    }

    public function test_validasi_menolak_tahun_selesai_lebih_awal(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Salah Tahun',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 1000,
                'jumlah_unit' => 10,
                'status' => 'komersil',
                'tahun_pembuatan' => 2022,
                'tahun_selesai' => 2019,
            ])
            ->assertSessionHasErrors('tahun_selesai');

        $this->assertSame(0, Perumahan::count());
    }

    public function test_perumahan_bisa_diubah_dan_dihapus(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Lama',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $this->actingAs($this->petugas())
            ->put(route('perumahan.update', $perumahan), [
                'nama' => 'Perumahan Baru',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 6000,
                'jumlah_unit' => 70,
                'status' => 'subsidi',
            ])
            ->assertRedirect(route('perumahan.show', $perumahan));

        $this->assertSame('Perumahan Baru', $perumahan->fresh()->nama);

        $this->actingAs($this->petugas())
            ->delete(route('perumahan.destroy', $perumahan))
            ->assertRedirect(route('perumahan.index'));

        $this->assertSoftDeleted('perumahans', ['id' => $perumahan->id]);
    }

    public function test_geojson_menyertakan_titik_yang_punya_koordinat(): void
    {
        $kelurahan = $this->wilayah();
        Perumahan::create([
            'nama' => 'Perumahan Berkordinat',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
            'latitude' => -6.9,
            'longitude' => 107.6,
        ]);
        Perumahan::create([
            'nama' => 'Perumahan Tanpa Koordinat',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $this->actingAs($this->petugas())
            ->get(route('perumahan.geojson'))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.geometry.coordinates.0', 107.6);
    }

    public function test_hanya_admin_yang_bisa_mengakses_manajemen_pengguna(): void
    {
        $this->actingAs($this->petugas())->get(route('users.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_batas_polygon_bisa_disimpan_dan_normalisasi(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Berpolygon',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 8000,
                'jumlah_unit' => 40,
                'status' => 'komersil',
                'batas_polygon' => [
                    ['-6.900000', '107.600000'],
                    ['-6.902000', '107.604000'],
                    ['-6.905000', '107.601000'],
                    ['-6.900000', '107.600000'], // titik penutup duplikat harus dibuang
                ],
            ])
            ->assertRedirect();

        $perumahan = Perumahan::where('nama', 'Perumahan Berpolygon')->firstOrFail();

        $this->assertCount(3, $perumahan->polygon_koordinat);
        $this->assertSame([-6.9, 107.6], $perumahan->polygon_koordinat[0]);
        $this->assertTrue($perumahan->punya_polygon);
    }

    public function test_batas_polygon_string_json_dari_form_bisa_disimpan(): void
    {
        // Browser mengirim input hidden sebagai string JSON, bukan array.
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Polygon String',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 8000,
                'batas_polygon' => '[[-6.9,107.6],[-6.902,107.604],[-6.905,107.601]]',
                'jumlah_unit' => 40,
                'status' => 'komersil',
            ])
            ->assertRedirect();

        $perumahan = Perumahan::where('nama', 'Perumahan Polygon String')->firstOrFail();

        $this->assertCount(3, $perumahan->polygon_koordinat);
        $this->assertTrue($perumahan->punya_polygon);
    }

    public function test_batas_polygon_string_kosong_dari_form_diabaikan(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Tanpa Polygon',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 8000,
                'jumlah_unit' => 40,
                'status' => 'komersil',
                'batas_polygon' => '[]',
            ])
            ->assertRedirect();

        $perumahan = Perumahan::where('nama', 'Perumahan Tanpa Polygon')->firstOrFail();

        $this->assertNull($perumahan->batas_polygon);
        $this->assertFalse($perumahan->punya_polygon);
    }

    public function test_batas_polygon_kurang_dari_tiga_titik_ditolak_validasi(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Polygon Kosong',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 8000,
                'jumlah_unit' => 40,
                'status' => 'komersil',
                'batas_polygon' => [
                    ['-6.9', '107.6'],
                    ['-6.902', '107.604'],
                ],
            ])
            ->assertSessionHasErrors('batas_polygon');

        $this->assertSame(0, Perumahan::count());
    }

    public function test_geojson_menyertakan_geometry_polygon(): void
    {
        $kelurahan = $this->wilayah();
        Perumahan::create([
            'nama' => 'Perumahan Polygon GeoJSON',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
            'latitude' => -6.9,
            'longitude' => 107.6,
            'batas_polygon' => [
                [-6.9, 107.6],
                [-6.902, 107.604],
                [-6.905, 107.601],
            ],
        ]);

        $this->actingAs($this->petugas())
            ->get(route('perumahan.geojson'))
            ->assertOk()
            ->assertJsonPath('features.0.geometry.type', 'Point')
            ->assertJsonPath('features.0.properties.geometry_polygon.type', 'Polygon')
            ->assertJsonCount(4, 'features.0.properties.geometry_polygon.coordinates.0');
    }

    public function test_geojson_menerima_perumahan_hanya_polygon_tanpa_titik(): void
    {
        $kelurahan = $this->wilayah();
        Perumahan::create([
            'nama' => 'Perumahan Hanya Polygon',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'subsidi',
            'batas_polygon' => [
                [-6.9, 107.6],
                [-6.902, 107.604],
                [-6.905, 107.601],
            ],
        ]);

        $this->actingAs($this->petugas())
            ->get(route('perumahan.geojson'))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.geometry', null)
            ->assertJsonPath('features.0.properties.nama', 'Perumahan Hanya Polygon');
    }

    public function test_luas_polygon_dihitung_dan_dibandingkan_dengan_luas_lahan(): void
    {
        $kelurahan = $this->wilayah();

        // Perkiraan: 1 menit bujur di sekitar lat -6.9 ≈ 1.845 m,
        // 1 menit lintang ≈ 1.853 m. Persegi 2' × 2' ≈ 3.706 × 3.690 m
        // ≈ 13,67 juta m² (≈ 1.367 ha).
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Hitung Luas',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 12660000,
            'jumlah_unit' => 100,
            'status' => 'komersil',
            'batas_polygon' => [
                [-6.90, 107.60],
                [-6.90, 107.633333333],
                [-6.933333333, 107.633333333],
                [-6.933333333, 107.60],
            ],
        ]);

        $luas = $perumahan->luas_polygon;

        $this->assertNotNull($luas);
        $this->assertGreaterThan(13000000, $luas);
        $this->assertLessThan(14000000, $luas);
        $this->assertEqualsWithDelta(1366.87, $perumahan->luas_polygon_ha, 0.5);

        // Selisih & deviasi terhadap luas lahan inputan (12.660.000 m²).
        $this->assertNotNull($perumahan->selisih_luas);
        $this->assertEqualsWithDelta($luas - 12660000, $perumahan->selisih_luas, 0.01);
        $this->assertEqualsWithDelta(
            ($luas - 12660000) / 12660000 * 100,
            $perumahan->deviasi_luas_persen,
            0.01,
        );

        // Luas polygon muncul di GeoJSON.
        $this->actingAs($this->petugas())
            ->get(route('perumahan.geojson'))
            ->assertOk()
            ->assertJsonPath('features.0.properties.luas_polygon', $luas);
    }

    public function test_luas_polygon_tanpa_polygon_atau_luas_lahan_nol(): void
    {
        $kelurahan = $this->wilayah();

        $tanpaPolygon = Perumahan::create([
            'nama' => 'Perumahan Tanpa Polygon Luas',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
        ]);

        $this->assertNull($tanpaPolygon->luas_polygon);
        $this->assertNull($tanpaPolygon->selisih_luas);
        $this->assertNull($tanpaPolygon->deviasi_luas_persen);

        $polygonTapiLuasNol = Perumahan::create([
            'nama' => 'Perumahan Polygon Luas Nol',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 0,
            'jumlah_unit' => 10,
            'status' => 'komersil',
            'batas_polygon' => [
                [-6.9, 107.6],
                [-6.902, 107.604],
                [-6.905, 107.601],
            ],
        ]);

        $this->assertNotNull($polygonTapiLuasNol->luas_polygon);
        $this->assertNull($polygonTapiLuasNol->selisih_luas);
        $this->assertNull($polygonTapiLuasNol->deviasi_luas_persen);
    }
}
