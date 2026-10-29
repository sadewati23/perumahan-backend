<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ThrottlePublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_perumahan_membatasi_per_ip_dan_ip_lain_tidak_terblokir(): void
    {
        RateLimiter::clear('publik|127.0.0.1');
        for ($i = 0; $i < 120; $i++) {
            $this->get(route('publik.perumahan.index'))->assertOk();
        }
        $this->get(route('publik.perumahan.index'))->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.2'])
            ->get(route('publik.perumahan.index'))->assertOk();
    }

    public function test_geojson_membatasi_enam_puluh_permintaan(): void
    {
        RateLimiter::clear('geojson|127.0.0.1');
        for ($i = 0; $i < 60; $i++) {
            $this->get(route('publik.peta.geojson'))->assertOk();
        }
        $this->get(route('publik.peta.geojson'))->assertStatus(429);
    }

    public function test_rute_admin_tidak_memakai_limiter_publik(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        RateLimiter::clear('publik|127.0.0.1');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }
}
