<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_css_memiliki_guard_reduced_motion_spinner_dan_print(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.animate-spin', $css);
        $this->assertStringContainsString('@media print', $css);
    }

    public function test_keyframe_baru_hanya_mengubah_transform_dan_opacity(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match_all('/@keyframes\s+([\w-]+)\s*\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER);

        $lama = ['kilau-teks', 'naik-masuk', 'batang-tumbuh', 'kartu-muncul'];
        $terlarang = ['color', 'background', 'border', 'shadow', 'fill', 'stroke', 'filter'];

        foreach ($matches as [$blok, $nama, $isi]) {
            if (in_array($nama, $lama, true)) continue;

            foreach ($terlarang as $properti) {
                $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($properti, '/').'\b/i', $isi, $nama);
            }
            preg_match_all('/([a-z-]+)\s*:/i', $isi, $properties);
            foreach ($properties[1] as $property) {
                $this->assertContains($property, ['transform', 'opacity', 'translate', 'scale'], $nama);
            }
        }
    }

    public function test_statistik_publik_memiliki_data_hitung_dengan_teks_server(): void
    {
        $html = $this->get(route('publik.beranda'))->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'data-hitung='));
        $this->assertSame(2, substr_count($html, 'data-hitung="0" data-desimal="2" aria-label="0,00">0,00</p>'));
        $this->assertSame(2, substr_count($html, 'data-hitung="0" data-desimal="0" aria-label="0">0</p>'));
    }

    public function test_kpi_dashboard_memiliki_data_hitung(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'data-hitung='));
        $this->assertStringContainsString('data-hitung="0" data-desimal="0"', $html);
        $this->assertStringContainsString('data-hitung="0" data-desimal="2"', $html);
    }

    public function test_hapus_laporan_dashboard_memakai_form_konfirmasi_global(): void
    {
        $html = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringContainsString('method="POST" action="{{ route(\'laporan.destroy\', $laporan) }}"', $html);
        $this->assertStringContainsString('@method(\'DELETE\')', $html);
        $this->assertStringContainsString('data-confirm="Hapus laporan', $html);
        $this->assertStringNotContainsString('data-action="', $html);
    }

    public function test_hapus_laporan_admin_memakai_form_konfirmasi_global(): void
    {
        $html = file_get_contents(resource_path('views/laporan/index.blade.php'));

        $this->assertSame(2, substr_count($html, 'method="POST" action="{{ route(\'laporan.destroy\', $laporan) }}"'));
        $this->assertSame(2, substr_count($html, '@method(\'DELETE\')'));
        $this->assertStringNotContainsString('data-action="', $html);
    }

    public function test_modul_javascript_animasi_terhubung(): void
    {
        $app = file_get_contents(resource_path('js/app.js'));
        $hitung = file_get_contents(resource_path('js/hitung-naik.js'));
        $flash = file_get_contents(resource_path('js/flash.js'));

        $this->assertFileExists(resource_path('js/hitung-naik.js'));
        $this->assertStringContainsString("from './hitung-naik'", $app);
        $this->assertStringContainsString('initHitungNaik()', $app);
        $this->assertStringContainsString('prefers-reduced-motion', $hitung);
        $this->assertStringContainsString('toast-keluar', $flash);
        $this->assertStringContainsString('prefers-reduced-motion', $flash);
    }
}
