<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use App\Services\DashboardStatistikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatusPerumahanTampilanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(DashboardStatistikService::CACHE_KEY);
    }

    private function buatData(): void
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kesambi', 'kabupaten_kota' => 'Kota Cirebon']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Drajat']);

        foreach (['subsidi', 'komersil', 'campuran'] as $status) {
            Perumahan::create([
                'nama' => 'Perumahan '.ucfirst($status).' Tampilan',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 10000,
                'jumlah_unit' => 80,
                'status' => $status,
                'latitude' => -6.7,
                'longitude' => 108.5,
            ]);
        }
    }

    public function test_palete_status_model_berbeda_dan_aman(): void
    {
        $status = collect(['subsidi', 'komersil', 'campuran'])->mapWithKeys(function (string $kode) {
            $perumahan = new Perumahan(['status' => $kode]);

            return [$kode => [$perumahan->status_badge_class, $perumahan->status_dot_class]];
        });

        $this->assertCount(3, $status->unique(fn (array $kelas) => implode('|', $kelas)));

        $kelas = $status->flatten()->implode(' ');
        $this->assertStringContainsString('bg-emerald-100', $kelas);
        $this->assertStringContainsString('bg-sky-100', $kelas);
        $this->assertStringContainsString('bg-violet-100', $kelas);
    }

    public function test_tiga_peta_memuat_warna_dan_legenda_campuran(): void
    {
        $this->buatData();

        foreach ([route('publik.peta'), route('publik.beranda'), route('perumahan.peta')] as $url) {
            $response = $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))->get($url);

            $response->assertOk()->assertSee('#7c3aed')->assertSee('Campuran');
        }
    }

    public function test_daftar_admin_memakai_lencana_violet_dan_dashboard_memakai_batang_violet(): void
    {
        $this->buatData();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('perumahan.index'))
            ->assertOk()
            ->assertSee('bg-violet-100 text-violet-800 ring-violet-600/20', false);

        Cache::forget(DashboardStatistikService::CACHE_KEY);
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Campuran')
            ->assertSee('bg-violet-500', false);
    }

    public function test_beranda_tidak_memakai_warna_oranye_status(): void
    {
        $this->buatData();

        $this->get(route('publik.beranda'))
            ->assertOk()
            ->assertDontSee('orange-500');
    }
}
