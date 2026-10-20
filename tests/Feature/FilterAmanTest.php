<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterAmanTest extends TestCase
{
    use RefreshDatabase;

    private function perumahan(string $nama): Perumahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Uji']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Kelurahan Uji']);

        return Perumahan::create([
            'nama' => $nama,
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
        ]);
    }

    public function test_input_filter_berbentuk_array_diabaikan(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);

        $this->get('/perumahan?q[]=x')->assertOk();
        $this->get('/perumahan?status[]=x&kecamatan_id[]=1')->assertOk();
        $this->get('/peta?q[]=x')->assertOk();
        $this->get('/lapor?jenis[]=x&perumahan[]=1')->assertOk();
        $this->actingAs($petugas)->get('/admin/laporan?q[]=x&jenis[]=x')->assertOk();
        $this->actingAs($petugas)->get('/admin/perumahan?q[]=x&tahun[]=2020')->assertOk();
    }

    public function test_filter_pencarian_menghindari_wildcard_like(): void
    {
        $this->perumahan('Alpha');
        $this->perumahan('Beta');

        $this->get('/perumahan?q=%')
            ->assertOk()
            ->assertDontSee('Alpha')
            ->assertDontSee('Beta');

        $this->get('/perumahan?q=Alpha')
            ->assertOk()
            ->assertSee('Alpha')
            ->assertDontSee('Beta');
    }
}
