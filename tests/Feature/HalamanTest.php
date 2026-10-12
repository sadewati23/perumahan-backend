<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function perumahan(): Perumahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);

        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Contoh',
            'kelurahan_id' => $kelurahan->id,
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
        ]);

        $perumahan->psu()->create(['jenis' => 'Jalan Lingkungan', 'jumlah' => 900, 'satuan' => 'meter', 'kondisi' => 'baik']);
        $perumahan->tipeUnits()->create(['tipe' => 'Tipe 36', 'jumlah_unit' => 80, 'luas_tanah' => 72, 'luas_bangunan' => 36]);

        return $perumahan;
    }

    public function test_semua_halaman_utama_bisa_dibuka_admin(): void
    {
        $perumahan = $this->perumahan();
        $admin = $this->admin();

        $halaman = [
            route('dashboard'),
            route('perumahan.index'),
            route('perumahan.create'),
            route('perumahan.show', $perumahan),
            route('perumahan.edit', $perumahan),
            route('perumahan.peta'),
            route('wilayah.index'),
            route('users.index'),
        ];

        foreach ($halaman as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_filter_pencarian_perumahan_bekerja(): void
    {
        $perumahan = $this->perumahan();

        $this->actingAs($this->admin())
            ->get(route('perumahan.index', ['q' => 'Contoh', 'status' => 'komersil']))
            ->assertOk()
            ->assertSee($perumahan->nama);

        $this->actingAs($this->admin())
            ->get(route('perumahan.index', ['status' => 'subsidi']))
            ->assertOk()
            ->assertDontSee($perumahan->nama);
    }

    public function test_dashboard_menampilkan_statistik_psu(): void
    {
        $perumahan = $this->perumahan();
        $perumahan->psu()->create(['jenis' => 'Saluran Drainase', 'jumlah' => 300, 'satuan' => 'meter', 'kondisi' => 'rusak_ringan']);
        $perumahan->psu()->create(['jenis' => 'Jalan Lingkungan', 'jumlah' => 500, 'satuan' => 'meter', 'kondisi' => 'rusak_berat']);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('PSU Rusak')
            ->assertSee('grafik-tahun')
            ->assertSee('grafik-kondisi')
            ->assertSee('grafik-jenis-psu');
    }

    public function test_kecamatan_yang_masih_punya_kelurahan_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $kecamatan = Kecamatan::create(['nama' => 'Mandalasari']);
        Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Cibangkong']);

        $this->actingAs($admin)
            ->delete(route('kecamatan.destroy', $kecamatan))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Kecamatan dan kelurahannya tetap utuh.
        $this->assertDatabaseHas('kecamatans', ['id' => $kecamatan->id]);
        $this->assertDatabaseHas('kelurahans', ['nama' => 'Cibangkong']);
    }

    public function test_admin_bisa_menambah_kecamatan_dan_kelurahan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('kecamatan.store'), [
            'nama' => 'Sukajadi',
            'kabupaten_kota' => 'Kota Bandung',
        ])->assertRedirect();

        $kecamatan = Kecamatan::where('nama', 'Sukajadi')->firstOrFail();

        $this->actingAs($admin)->post(route('kelurahan.store'), [
            'kecamatan_id' => $kecamatan->id,
            'nama' => 'Pasteur',
        ])->assertRedirect();

        $this->assertDatabaseHas('kelurahans', ['nama' => 'Pasteur', 'kecamatan_id' => $kecamatan->id]);
    }
}
